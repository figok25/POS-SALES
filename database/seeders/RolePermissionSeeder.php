<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role & Permission foundation (Blueprint #17 Role dan Authorization,
 * #47 Admin Navigation, #48 Sales Navigation).
 *
 * Multi Branch/Depo (revisi): tiga role bisnis - SUPER_ADMIN (global,
 * lintas Branch), ADMIN (satu Branch), dan SALES (satu Branch). Permission
 * dikelompokkan per modul mengikuti struktur menu pada blueprint.
 *
 * PENTING: 'system.manage' SENGAJA DICABUT dari role admin (dulu Admin
 * ikut memilikinya). User/Role/Permission/Settings sekarang hanya boleh
 * diakses Super Admin - digate ganda lewat middleware 'role:super_admin'
 * DI ROUTE (routes/admin_system.php), bukan cuma lewat permission ini.
 * Kalaupun permission ini suatu saat tersemai ulang ke role admin secara
 * tidak sengaja, gate 'role:super_admin' di route tetap menolaknya.
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
            // Reports & Dashboard
            'reports.view',
            'dashboard.admin.view',
            // Audit Log tetap boleh dilihat Admin (scope Branch sendiri -
            // lihat AuditLogController); yang TIDAK boleh adalah
            // User/Role/Permission/Settings (system.manage, di bawah).
            'audit-log.view',
        ];

        $salesPermissions = [
            'dashboard.sales.view',
            'customer.view', 'customer.manage',
            'tagging-toko.manage',
            'kunjungan.manage',
            'sales-transaction.create', 'sales-transaction.view',
            'sales-stock.view',
            // Business Flow Update v3.1 (Blueprint #13.11): Sales submit Return
            // Stock lewat Sales Mobile, otomatis membuat BTB Distribusi.
            'sales-stock.return',
            'invoice.view-own',
            'payment.create',
            // Live Sales Field Operations (Blueprint #14, #21)
            'sales-task.view', 'tracking.manage',
        ];

        // Multi Branch/Depo: kewenangan eksklusif Super Admin - mengelola
        // User/Role/Permission lintas Branch, dan memilih Branch context
        // (lihat BranchContext, bukan permission - tapi tetap didaftarkan
        // di sini sebagai permission bisnis untuk konsistensi dengan modul
        // lain & defense-in-depth di route).
        $superAdminOnlyPermissions = [
            'system.manage',
            'branch.manage',
            // Live Monitoring Sales: hanya Super Admin.
            'live-monitoring.view',
        ];

        $allPermissions = array_unique(array_merge($adminPermissions, $salesPermissions, $superAdminOnlyPermissions));

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Super Admin: SEMUA permission (admin + sales + eksklusif) supaya
        // tidak pernah terblokir oleh permission check di controller/view
        // manapun - batasan sesungguhnya ada di BranchContext (data scope),
        // bukan di permission (kewenangan fitur).
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions($allPermissions);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($adminPermissions);

        $salesRole = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $salesRole->syncPermissions($salesPermissions);
    }
}
