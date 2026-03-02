@php
    use Illuminate\Support\Str;
@endphp

<x-filament::widget class="latest-articles-widget col-span-full">
    <x-filament::card>
        @php
            $articles = $this->getViewData()['articles'] ?? [];
        @endphp

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                أخبار و مستجدات <span class="text-primary-500">المديرية</span>
            </h2>
            <a href="#" class="px-3 py-1 text-sm bg-primary-100 text-primary-800 rounded-md">
                كل المستجدات
            </a>
        </div>

        @if(count($articles) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-6">
                @foreach ($articles as $article)
                    <div 
                        x-data="{ open: false }" 
                        class="bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden"
                    >
                        <!-- صورة + شارة -->
                        <div class="relative h-40 overflow-hidden cursor-pointer" @click="open = true">
                            <img src="{{ asset('storage/uploads/articles/' . $article->image) }}"
                                 alt="{{ $article->title }}"
                                 class="w-full h-full object-cover transition-transform duration-300 hover:scale-105" />
                            <span class="absolute top-2 right-2 bg-primary-500 text-white text-xs px-2 py-1 rounded-full">
                                {{ $article->category->name ?? 'عام' }}
                            </span>
                        </div>
                        
                        <!-- محتوى -->
                        <div class="p-3 flex flex-col justify-between">
                            <h3 
                                class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2 line-clamp-2 cursor-pointer"
                                @click="open = true"
                            >
                                {{ $article->title }}
                            </h3>
                            <div class="flex justify-between items-center border-t pt-2 border-gray-100 dark:border-gray-700 mt-auto">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $article->created_at->format('Y-m-d') }}
                                </span>
                                <button 
                                    @click="open = true"
                                    class="text-primary-500 text-xs hover:underline"
                                >
                                    قراءة المزيد
                                </button>
                            </div>
                        </div>

                        <!-- النافذة المنبثقة -->
                        <div 
                            x-show="open"
                            x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-50 overflow-y-auto"
                            style="display: none;"
                           
                        >
                            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                                <!-- خلفية -->
                                <div 
                                    class="fixed inset-0 bg-gray-100 bg-opacity-75" 
                                    @click="open = false"
                                ></div>

                                <!-- محتوى -->
                                <div 
                                    class="bg-white dark:bg-gray-200 rounded-lg shadow-xl transform transition-all sm:max-w-2xl w-56 mx-auto relative z-50 text-left overflow-hidden"
                                    @click.stop
                                >
                                    <div class="p-6 center justify-center w-48">
                                        <div class="flex  justify-between items-start mb-4">
                                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                                                {{ $article->title }}
                                            </h3>
                                            <button 
                                                @click="open = false"
                                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        @if($article->image)
                                            <div class=" h-68 overflow-hidden center ">
                                                <img src="{{ asset('storage/uploads/articles/' . $article->image) }}"
                                                     alt="{{ $article->title }}"
                                                     class="w-48 h-48 object-cover center">
                                            </div>
                                        @endif
                                        <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                                            {!! nl2br(e($article->content)) !!}
                                        </div>
                                        <div class="mt-6 flex justify-end">
                                            <button 
                                                @click="open = false"
                                                class="px-4 py-2 bg-primary-500 text-white rounded-md hover:bg-primary-600 transition"
                                            >
                                                إغلاق
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-8 text-center">
                <h3 class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">لا توجد مقالات متاحة</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">لم يتم نشر أي مقالات بعد</p>
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>
