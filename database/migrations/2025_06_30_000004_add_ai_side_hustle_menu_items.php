<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the parent menu ID or create a parent menu
        $parentId = DB::table('menus')->insertGetId([
            'parent' => null,
            'label' => 'AI Side Hustle',
            'type' => 'item-dropdown',
            'href' => '#',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0"></path><path d="M4 6v6a8 3 0 0 0 16 0v-6"></path><path d="M4 12v6a8 3 0 0 0 16 0v-6"></path></svg>',
            'route' => null,
            'route_params' => null,
            'params' => null,
            'target' => '_self',
            'order' => 50,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Add submenu items
        $subMenuItems = [
            [
                'parent' => $parentId,
                'label' => 'Dashboard',
                'type' => 'item',
                'href' => null,
                'icon' => null,
                'route' => 'dashboard.ai-side-hustle.index',
                'route_params' => null,
                'params' => null,
                'target' => '_self',
                'order' => 1
            ],
            [
                'parent' => $parentId,
                'label' => 'My Projects',
                'type' => 'item',
                'href' => null,
                'icon' => null,
                'route' => 'dashboard.contexts.index',
                'route_params' => null,
                'params' => null,
                'target' => '_self',
                'order' => 2
            ],
            [
                'parent' => $parentId,
                'label' => 'Business Ideas',
                'type' => 'item',
                'href' => null,
                'icon' => null,
                'route' => 'dashboard.business-ideas.index',
                'route_params' => null,
                'params' => null,
                'target' => '_self',
                'order' => 3
            ],
            [
                'parent' => $parentId,
                'label' => null,
                'type' => 'divider',
                'href' => null,
                'icon' => null,
                'route' => null,
                'route_params' => null,
                'params' => null,
                'target' => '_self',
                'order' => 4
            ],
            [
                'parent' => $parentId,
                'label' => 'Generate Ideas',
                'type' => 'item',
                'href' => null,
                'icon' => null,
                'route' => 'dashboard.business-ideas.generate',
                'route_params' => null,
                'params' => null,
                'target' => '_self',
                'order' => 5
            ],
            [
                'parent' => $parentId,
                'label' => 'Preferences',
                'type' => 'item',
                'href' => null,
                'icon' => null,
                'route' => 'dashboard.ai-side-hustle.preferences',
                'route_params' => null,
                'params' => null,
                'target' => '_self',
                'order' => 6
            ]
        ];

        foreach ($subMenuItems as $item) {
            $item['created_at'] = now();
            $item['updated_at'] = now();
            DB::table('menus')->insert($item);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Find the parent menu
        $parent = DB::table('menus')->where('label', 'AI Side Hustle')->first();
        
        if ($parent) {
            // Delete all child menu items
            DB::table('menus')->where('parent', $parent->id)->delete();
            
            // Delete the parent menu
            DB::table('menus')->where('id', $parent->id)->delete();
        }
    }
};