<?php

    use Livewire\Volt\Component;

    new class extends Component {
    //
    }
?>

<x-layouts.app.content :title="__('Dashboard')" heading='Good Morning, {{ auth()->user()->name }}!'>

    
    <x-slot:breadcrumbs>
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home"/>
    </x-slot:breadcrumbs>
    
    <!-- Analitics -->
    <div class="card flex h-full w-full flex-1 flex-col gap-4 ">
        <flux:heading>My Automation Analitics</flux:heading>
        <div class="grid auto-rows-min gap-4 sm:grid-cols-2 md:grid-cols-4">
            
            <div class="p-4 space-y-3 bg-primary/10 rounded-xl border border-primary/50">
                <div class="relative flex items-center justify-between leading-none">
                    <div class="before:rounded before:w-1 before:absolute before:bg-primary before:-left-4.5 before:top-1.5 before:bottom-1.5 ">
                        <flux:text>All Time</flux:text>
                        <flux:heading level="4" size="xl" class="mt-0!">1.457%</flux:heading>
                    </div>
                    <div class="size-8 flex items-center justify-center bg-primary/10 rounded">
                        <flux:icon.chart-bar-square class="text-primary" />   
                    </div>
                </div>
                <flux:text>X-Ray leads Captured</flux:text>
            </div>

            <div class="p-4  space-y-3 bg-secondary/10 rounded-xl border border-secondary/50">
                <div class="relative flex items-center justify-between leading-none">
                    <div class="before:rounded before:w-1 before:absolute before:-left-4.5 before:top-1.5 before:bottom-1.5 before:bg-secondary ">
                        <flux:text>All Time</flux:text>
                        <flux:heading level="4" size="xl" class="mt-0!">1.457%</flux:heading>
                    </div>
                    <div class="size-8 flex items-center justify-center bg-secondary/10 rounded">
                        <flux:icon.chart-bar-square class="text-secondary" />   
                    </div>
                </div>
                <flux:text>X-Ray leads Captured</flux:text>
            </div>

            <div class="p-4 space-y-3 bg-success/10 rounded-xl border border-success/50">
                <div class="relative flex items-center justify-between leading-none">
                    <div class="before:rounded before:w-1 before:absolute before:bg-success before:-left-4.5 before:top-1.5 before:bottom-1.5 ">
                        <flux:text>All Time</flux:text>
                        <flux:heading level="4" size="xl" class="mt-0!">1.457%</flux:heading>
                    </div>
                    <div class="size-8 flex items-center justify-center bg-success/10 rounded">
                        <flux:icon.chart-bar-square class="text-success" />   
                    </div>
                </div>
                <flux:text>X-Ray leads Captured</flux:text>
            </div>

            <div class="p-4  space-y-3 bg-warning/10 rounded-xl border border-warning/50">
                <div class="relative flex items-center justify-between leading-none">
                    <div class="before:rounded before:w-1 before:absolute before:bg-warning before:-left-4.5 before:top-1.5 before:bottom-1.5 ">
                        <flux:text>All Time</flux:text>
                        <flux:heading level="4" size="xl" class="mt-0!">1.457%</flux:heading>
                    </div>
                    <div class="size-8 flex items-center justify-center bg-warning/10 rounded">
                        <flux:icon.chart-bar-square class="text-warning" />   
                    </div>
                </div>
                <flux:text>X-Ray leads Captured</flux:text>
            </div>

        </div>
    </div>

    <div class="grid grid-cols-12 gap-4">

        <div class="col-span-12 sm:col-span-8 space-y-4">


            <div class="card">

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="">
                        <flux:heading level="4" class="pb-0.5">Activation Steps</flux:heading>
                        <flux:separator variant="subtle"/>
                        <div class="pt-1">
                            @for ($i = 1; $i <= 4; $i++)
                            <div class="py-1 flex items-center gap-2">
                                <div class="bg-secondary/10 rounded-full size-7 flex items-center justify-center">
                                    <flux:icon.academic-cap class="text-secondary size-5"/>
                                </div>
                                <div class="flex-1">
                                    <flux:text class="text-sm leading-none font-medium">Text Base</flux:text>
                                    <flux:text class="text-xs leading-none mt-0">Text xs</flux:text>
                                </div>
                                <div class="flex items-center justify-center">
                                    <flux:text class="text-xs leading-none">9:45 am</flux:text>
                                </div>
                            </div>
                            <flux:separator variant="subtle"/>
                            @endfor
                        </div>
                        <flux:button icon="plus" class="mt-4 w-full">View</flux:button>
                    </div>

                    <div class="">
                        <flux:heading level="4" class="pb-0.5">Activation Steps</flux:heading>
                        <flux:separator variant="subtle"/>
                        <div class="pt-1">
                            @for ($i = 1; $i <= 4; $i++)
                            <div class="py-1 flex items-center gap-2">
                                <div class="bg-secondary/10 rounded-full size-7 flex items-center justify-center">
                                    <flux:icon.academic-cap class="text-secondary size-5"/>
                                </div>
                                <div class="flex-1">
                                    <flux:text class="text-sm leading-none font-medium">Text Base</flux:text>
                                    <flux:text class="text-xs leading-none mt-0">Text xs</flux:text>
                                </div>
                                <div class="flex items-center justify-center">
                                    <flux:text class="text-xs leading-none">9:45 am</flux:text>
                                </div>
                            </div>
                            <flux:separator variant="subtle"/>
                            @endfor
                        </div>
                        <flux:button icon="plus" class="mt-4 w-full">View</flux:button>
                    </div>
                </div>  
                    
            </div>

            <div class="grid sm:grid-cols-2 gap-4">

                <div class="card flex flex-col overflow-visible min-h-auto">
                    <div class="flex items-center justify-between pb-0.5">
                        <flux:heading level="4" size="sm">
                            Connect Email Outreach
                        </flux:heading>
                        <flux:button variant="subtle" icon="ellipsis-horizontal" iconVariant="micro" size="xs"/>
                    </div>
                    
                    <flux:text class="text-primary">
                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae,
                    </flux:text>
                    
                    <flux:text class="text-info">
                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae,
                    </flux:text>

                    <flux:spacer/>
                    
                    <flux:button icon="plus" class="mt-4 w-full">Create</flux:button>
                </div>
                
                <div class="card flex flex-col overflow-visible min-h-auto">
                    <div class="flex items-center justify-between pb-0.5">
                        <flux:heading level="4" size="sm">
                            Create a new Automation
                        </flux:heading>
                        <flux:button variant="subtle" icon="ellipsis-horizontal" iconVariant="micro" size="xs"/>
                    </div>
                    
                    <flux:text>
                        accusantium rerum doloribus omnis id, culpa, laborum facere eos est quas ab aspernatur!
                    </flux:text>

                    <flux:spacer/>
                    
                    <flux:button icon="plus" class="mt-4 w-full">Create</flux:button>
                </div>

            </div>

            <div class="card">

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="">
                        <flux:heading level="4" class="pb-0.5">Activation Steps</flux:heading>
                        <flux:separator variant="subtle"/>
                        <div class="pt-1">
                            @for ($i = 1; $i <= 4; $i++)
                            <div class="py-1 flex items-center gap-2">
                                <div class="bg-secondary/10 rounded-full size-7 flex items-center justify-center">
                                    <flux:icon.academic-cap class="text-secondary size-5"/>
                                </div>
                                <div class="flex-1">
                                    <flux:text class="text-sm leading-none font-medium">Text Base</flux:text>
                                    <flux:text class="text-xs leading-none mt-0">Text xs</flux:text>
                                </div>
                                <div class="flex items-center justify-center">
                                    <flux:text class="text-xs leading-none">9:45 am</flux:text>
                                </div>
                            </div>
                            <flux:separator variant="subtle"/>
                            @endfor
                        </div>
                        <flux:button icon="plus" class="mt-4 w-full">View</flux:button>
                    </div>

                    <div class="">
                        <flux:heading level="4" class="pb-0.5">Activation Steps</flux:heading>
                        <flux:separator variant="subtle"/>
                        <div class="pt-1">
                            @for ($i = 1; $i <= 4; $i++)
                            <div class="py-1 flex items-center gap-2">
                                <div class="bg-secondary/10 rounded-full size-7 flex items-center justify-center">
                                    <flux:icon.academic-cap class="text-secondary size-5"/>
                                </div>
                                <div class="flex-1">
                                    <flux:text class="text-sm leading-none font-medium">Text Base</flux:text>
                                    <flux:text class="text-xs leading-none mt-0">Text xs</flux:text>
                                </div>
                                <div class="flex items-center justify-center">
                                    <flux:text class="text-xs leading-none">9:45 am</flux:text>
                                </div>
                            </div>
                            <flux:separator variant="subtle"/>
                            @endfor
                        </div>
                        <flux:button icon="plus" class="mt-4 w-full">View</flux:button>
                    </div>
                </div>  
                    
            </div>

            <div class="card">

            </div>

        </div>

        <div class="col-span-12 sm:col-span-4">
            <div class="card">
                <flux:text>
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae, accusantium rerum doloribus omnis id, culpa, laborum facere eos est quas ab aspernatur!
                </flux:text>
                <flux:text>
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae, accusantium rerum doloribus omnis id, culpa, laborum facere eos est quas ab aspernatur!
                </flux:text>
                <flux:text>
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae, accusantium rerum doloribus omnis id, culpa, laborum facere eos est quas ab aspernatur!
                </flux:text>
                <flux:text>
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae, accusantium rerum doloribus omnis id, culpa, laborum facere eos est quas ab aspernatur!
                </flux:text>
                <flux:text>
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae, accusantium rerum doloribus omnis id, culpa, laborum facere eos est quas ab aspernatur!
                </flux:text>
                <flux:text>
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae, accusantium rerum doloribus omnis id, culpa, laborum facere eos est quas ab aspernatur!
                </flux:text>
                <flux:text>
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolor nostrum optio assumenda consectetur enim tempora a beatae, accusantium rerum doloribus omnis id, culpa, laborum facere eos est quas ab aspernatur!
                </flux:text>
            </div>
        </div>

    </div>

</x-layouts.app.content>