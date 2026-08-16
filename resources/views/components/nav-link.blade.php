@props(['active' => false, 'icon' => 'grid'])
<a {{ $attributes->merge(['class' => 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition '.($active ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/20' : 'text-slate-400 hover:bg-white/10 hover:text-white')]) }}><x-icon :name="$icon" class="h-5 w-5 shrink-0" /> <span>{{ $slot }}</span></a>
