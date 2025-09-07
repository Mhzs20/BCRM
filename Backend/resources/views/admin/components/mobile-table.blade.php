{{-- Mobile-friendly table component --}}
@props(['items', 'fields', 'actions' => []])

<div class="block sm:hidden">
    @forelse ($items as $item)
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3 shadow-sm">
            @foreach ($fields as $field => $config)
                <div class="flex justify-between items-center py-1 text-sm {{ $loop->last ? '' : 'border-b border-gray-100 pb-2 mb-2' }}">
                    <span class="font-medium text-gray-600">{{ $config['label'] }}:</span>
                    <span class="text-gray-900 text-right max-w-[60%] truncate">
                        @if (isset($config['component']))
                            @include($config['component'], ['item' => $item])
                        @else
                            {{ data_get($item, $field) ?? 'N/A' }}
                        @endif
                    </span>
                </div>
            @endforeach
            
            @if (!empty($actions))
                <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-2">
                    @foreach ($actions as $action)
                        @if (isset($action['component']))
                            @include($action['component'], ['item' => $item])
                        @else
                            <a href="{{ str_replace(':id', $item->id, $action['url']) }}" 
                               class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md {{ $action['class'] ?? 'text-indigo-700 bg-indigo-100 hover:bg-indigo-200' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150">
                                @if (isset($action['icon']))
                                    <i class="{{ $action['icon'] }} ml-1"></i>
                                @endif
                                {{ $action['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="p-6 text-center text-gray-500">
            {{ $emptyMessage ?? 'هیچ آیتمی یافت نشد.' }}
        </div>
    @endforelse
</div>
