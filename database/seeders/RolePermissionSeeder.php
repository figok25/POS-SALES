<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role & Permission foundation (Blueprint #17 Role dan Authorization,
 * #47 Admin Navigation, #48 Sales Navigation).
 *
 * Hanya dua role utama: ADMIN dan SALES. Permission dikelompokkan per
 * modul mengikuti struktur menu pada blueprint. Modul bisnis yang belum
 * dibangun (BKB, BTB, Invoice, dll) tetap didaftarkan permission-nya di
 * sini sebagai fondasi, meski fitur/controller-nya baru dikerjakan pada
 * fase-fase berikutnya.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $adminPermissions = [
            // Master Data
            'master-data.view', 'master-data.manage',
            // Inventory
            'inventory.view', 'inventory.manage',
            // Distribution (Permintaan Barang, BKB, BTB, Branch Transfer)
            'distribution.view', 'distribution.manage', 'distribution.apply',
            // Sales Management (sisi Admin)
            'sales-management.view', 'sales-management.manage',
            // Customer Assignment (Fase 6 Hardening, Blueprint #729)
            'customer-assignment.view', 'customer-assignment.manage',
            // Finance (Invoice, Payment, Settlement)
            'finance.view', 'finance.manage',
            // Operations (Delivery Order, Route, Vehicle, Driver)
            'operations.view', 'operations.manage',
            // Sales Task / Penugasan & Live Monitoring (Live Sales Field
            // Operations Blueprint #14, #28)
            'sales-task.view', 'sales-task.manage',
            'live-monitoring.view',
            // Reports & Dashboard
            'reports.view',
            'dashboard.admin.view',
            // System (Users, Roles, Permissions, Audit Log, Settings)
            'system.manage',
            'audit-log.view',
        ];

        $salesPermissions = [
            'dashboard.sales.view',
            'customer.view', 'customer.manage',
            'tagging-toko.manage',
            'kunjungan.manage',
            'sales-transaction.create', 'sales-transaction.view',
            'sales-stock.view',
            'invoice.view-own',
            'payment.create',
            // Live Sales Field Operations (Blueprint #14, #21)
            'sales-task.view', 'tracking.manage',
        ];

        foreach (array_unique(array_merge($adminPermissions, $salesPermissions)) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($adminPermissions);

        $salesRole = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $salesRole->syncPermissions($salesPermissions);
    }
}
