@props(['title', 'value', 'iconName' => 'user', 'desc' => '', 'hasLink' => true])

<div class="bg-bg-pr rounded-2xl p-6 relative group cursor-pointer smoothing hover:-translate-y-1 hover:shadow-2xl shadow-fill-qt">
	<div class="flex flex-col h-44">
		<div class="shrink-0">
			<div class="size-12 rounded-full bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
				<x-ui-icon name="{{ $iconName }}" type="line" class="size-6 text-brand-600 dark:text-brand-400"></x-ui-icon>
			</div>
		</div>
		<div class="mb-8">
			<span class="text-title-3 block text-lab-pr2 font-bold mt-2">{{ $title }}</span>
			<span class="text-par-s block text-lab-sc">
				{{ $desc }}
			</span>
		</div>
		<span class="mt-auto text-title-1 leading-none block text-lab-pr2 font-bold font-outfit tracking-tight">{{ $value }}</span>
		
		@if($hasLink)
			<div class="absolute bottom-4 right-4 size-icon-medium text-lab-sc group-hover:text-brand-900">
				<x-ui-icon name="arrow-up-right" type="line"></x-ui-icon>
			</div>
		@endif
	</div>
</div>