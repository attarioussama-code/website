#!/usr/bin/env python3
"""
Generate simulated PCAP with mixed benign and malicious-like traffic.
Usage:
    python generate_simulated_traffic.py --output simulated.pcap --target 192.168.56.101
"""

import argparse
import random
import time
from scapy.all import IP, TCP, UDP, ICMP, Ether, wrpcap

# -----------------------------
# Helper: generate timestamps
# -----------------------------
def make_timestamps(count, start_ts=None, spacing=0.01):
    if start_ts is None:
        start_ts = time.time()
    return [start_ts + i * spacing for i in range(count)]

# -----------------------------
# Benign traffic (simple web-like flows)
# -----------------------------
def gen_benign_web(src_net_prefix="10.0.0.", client_start=2, clients=5, requests_per_client=6, server_ip="192.168.0.10", base_time=None):
    pkts = []
    # each request produces 4 packets: SYN, SYN/ACK, ACK, RESP
    total_packets = clients * requests_per_client * 4
    ts = make_timestamps(total_packets, start_ts=base_time, spacing=0.2)
    idx = 0
    for c in range(clients):
        src = src_net_prefix + str(client_start + c)
        sport_base = 1024 + random.randint(0, 40000)
        for r in range(requests_per_client):
            sport = sport_base + r
            # TCP 3-way handshake (SYN, SYN/ACK, ACK) + response
            syn = IP(src=src, dst=server_ip)/TCP(sport=sport, dport=80, flags="S")
            syn.time = ts[idx]; idx += 1
            synack = IP(src=server_ip, dst=src)/TCP(sport=80, dport=sport, flags="SA")
            synack.time = ts[idx]; idx += 1
            ack = IP(src=src, dst=server_ip)/TCP(sport=sport, dport=80, flags="A")/"GET / HTTP/1.1\r\n\r\n"
            ack.time = ts[idx]; idx += 1
            resp = IP(src=server_ip, dst=src)/TCP(sport=80, dport=sport, flags="PA")/"HTTP/1.1 200 OK\r\nContent-Length: 12\r\n\r\nHello World\n"
            resp.time = ts[idx]; idx += 1
            pkts += [syn, synack, ack, resp]
    return pkts

# -----------------------------
# Port scan (SYN scan)
# -----------------------------
def gen_port_scan(scanner="10.0.1.5", target="192.168.0.10", ports=None, base_time=None):
    if ports is None:
        ports = list(range(20, 102))
    pkts = []
    ts = make_timestamps(len(ports)*2, start_ts=base_time, spacing=0.05)
    idx = 0
    for p in ports:
        syn = IP(src=scanner, dst=target)/TCP(sport=random.randint(2000,65000), dport=p, flags="S")
        syn.time = ts[idx]; idx += 1
        # reply: sometimes open (SYN/ACK) sometimes closed (RST)
        if random.random() < 0.08:  # simulate some open ports
            synack = IP(src=target, dst=scanner)/TCP(sport=p, dport=syn[TCP].sport, flags="SA")
        else:
            synack = IP(src=target, dst=scanner)/TCP(sport=p, dport=syn[TCP].sport, flags="R")
        synack.time = ts[idx]; idx += 1
        pkts += [syn, synack]
    return pkts

# -----------------------------
# SYN flood (many SYNs, few replies)
# -----------------------------
def gen_syn_flood(attacker_prefix="10.9.9.", attacker_start=2, attackers=4, target="192.168.0.10", target_port=80, syns_per_attacker=300, base_time=None):
    total = attackers * syns_per_attacker
    pkts = []
    ts = make_timestamps(total, start_ts=base_time, spacing=0.002)
    idx = 0
    for a in range(attackers):
        src = attacker_prefix + str(attacker_start + a)
        for s in range(syns_per_attacker):
            syn = IP(src=src, dst=target)/TCP(sport=random.randint(1025,65000), dport=target_port, flags="S")
            syn.time = ts[idx]; idx += 1
            # Most SYNs get no reply in a flood simulation (simulate drop)
            pkts.append(syn)
    return pkts

# -----------------------------
# ICMP flood
# -----------------------------
def gen_icmp_flood(attacker="10.9.8.8", target="192.168.0.10", count=200, base_time=None):
    pkts = []
    ts = make_timestamps(count, start_ts=base_time, spacing=0.005)
    for i in range(count):
        p = IP(src=attacker, dst=target)/ICMP()/("X"*random.randint(20,200))
        p.time = ts[i]
        pkts.append(p)
    return pkts

# -----------------------------
# C2-like beacon traffic (periodic small UDP/TCP to specific ports)
# -----------------------------
def gen_c2_beacon(beacon_host="10.0.2.7", target="192.168.0.10", ports=None, beacons=30, base_time=None):
    if ports is None:
        ports = [4444, 8080, 9001]
    pkts = []
    ts = make_timestamps(beacons * len(ports), start_ts=base_time, spacing=1.5)
    idx = 0
    for b in range(beacons):
        for p in ports:
            # alternate UDP/TCP
            if random.random() < 0.5:
                pkt = IP(src=beacon_host, dst=target)/UDP(sport=random.randint(2000,65000), dport=p)/("BEACON %d" % b)
            else:
                pkt = IP(src=beacon_host, dst=target)/TCP(sport=random.randint(2000,65000), dport=p, flags="PA")/("HELLO %d" % b)
            pkt.time = ts[idx]; idx += 1
            pkts.append(pkt)
    return pkts

# -----------------------------
# Main orchestration
# -----------------------------
def build_mixed_pcap(output, target_ip, base_time=None):
    if base_time is None:
        base_time = time.time()

    pkts = []
    t = base_time

    # benign
    pkts += gen_benign_web(src_net_prefix="10.0.0.", client_start=2, clients=3, requests_per_client=4, server_ip=target_ip, base_time=t)
    t += 20

    # small port scan
    pkts += gen_port_scan(scanner="10.0.1.5", target=target_ip, ports=list(range(20,60)), base_time=t)
    t += 3

    # C2 beacons
    pkts += gen_c2_beacon(beacon_host="10.0.2.7", target=target_ip, ports=[4444,8080], beacons=20, base_time=t)
    t += 40

    # ICMP flood
    pkts += gen_icmp_flood(attacker="10.9.8.8", target=target_ip, count=150, base_time=t)
    t += 2

    # SYN flood
    pkts += gen_syn_flood(attacker_prefix="10.9.9.", attacker_start=2, attackers=3, target=target_ip, target_port=80, syns_per_attacker=250, base_time=t)
    t += 5

    # shuffle a little to intermix types (optional) while preserving timestamps we already set
    pkts_sorted = sorted(pkts, key=lambda p: getattr(p, "time", 0.0))

    print(f"[+] Total packets generated: {len(pkts_sorted)}")
    wrpcap(output, pkts_sorted)
    print(f"[+] Written to {output}")

# -----------------------------
# CLI
# -----------------------------
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Generate simulated PCAP with mixed benign + attack traffic.")
    parser.add_argument("--output", "-o", default="simulated.pcap", help="Output pcap filename")
    parser.add_argument("--target", "-t", required=True, help="Target IP address to simulate traffic towards (victim/server)")
    args = parser.parse_args()
    build_mixed_pcap(args.output, args.target)
