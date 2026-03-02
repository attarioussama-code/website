document.addEventListener('DOMContentLoaded', function () {
    // تفعيل تأثيرات البطاقات
    const articleCards = document.querySelectorAll('.article-card');

    articleCards.forEach(card => {
        // تأثير عند النقر
        card.addEventListener('click', function (e) {
            // منع التنقل إذا تم النقر على رابط "اقرأ المزيد"
            if (e.target.tagName === 'A') return;

            const articleId = card.dataset.articleId;
            if (articleId) {
                window.location.href = `/articles/${articleId}`;
            }
        });

        // تأثيرات إضافية عند التحويم
        card.addEventListener('mouseenter', function () {
            this.querySelector('.read-more').style.color = '#ea580c';
        });

        card.addEventListener('mouseleave', function () {
            this.querySelector('.read-more').style.color = '#f97316';
        });
    });

    // تهيئة Lazy Loading للصور
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    } else {
        // Fallback باستخدام Intersection Observer
        const lazyImages = [].slice.call(document.querySelectorAll('img[loading="lazy"]'));

        if ('IntersectionObserver' in window) {
            const lazyImageObserver = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        const lazyImage = entry.target;
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImageObserver.unobserve(lazyImage);
                    }
                });
            });

            lazyImages.forEach(function (lazyImage) {
                lazyImageObserver.observe(lazyImage);
            });
        }
    }
});