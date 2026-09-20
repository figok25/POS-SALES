<?php

app('router')->setCompiledRoutes(
    array (
  'compiled' => 
  array (
    0 => false,
    1 => 
    array (
      '/sanctum/csrf-cookie' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sanctum.csrf-cookie',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/up' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::3r4u7gHVSNOHtClA',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::zaqYOsPnBA9Jmccp',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/profile' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'profile.edit',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'profile.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'profile.destroy',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/companies' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.companies.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.companies.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/companies/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.companies.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/branches' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.branches.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.branches.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/branches/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.branches.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/warehouses' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.warehouses.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.warehouses.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/warehouses/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.warehouses.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/categories' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.categories.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.categories.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/categories/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.categories.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/units' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.units.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.units.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/units/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.units.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/products' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.products.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.products.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/products/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.products.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/prices' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.prices.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.prices.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/prices/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.prices.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/employees' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.employees.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.employees.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/employees/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.employees.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/sales' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.sales.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.sales.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/sales/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.sales.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/customers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.customers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.customers.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/customers/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.customers.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/vehicles' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.vehicles.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.vehicles.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/vehicles/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.vehicles.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/suppliers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.suppliers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.suppliers.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/master/suppliers/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.suppliers.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/inventory/stock' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.inventory.stock.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/inventory/movements' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.inventory.movements.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/inventory/adjustments' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.inventory.adjustments.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.inventory.adjustments.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/inventory/adjustments/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.inventory.adjustments.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/distribution/stock-requests' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.stock-requests.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.stock-requests.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/distribution/bkb' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.bkb.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.bkb.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/distribution/btb' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.btb.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.btb.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/distribution/branch-transfer' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.branch-transfer.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.branch-transfer.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/distribution/stock-requests-create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.stock-requests.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/distribution/bkb-create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.bkb.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/distribution/btb-create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.btb.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/distribution/branch-transfer-create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.branch-transfer.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sales/customer-taggings' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.customer-taggings.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sales/visits' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.visits.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sales/transactions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.transactions.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sales/invoices' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.invoices.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sales/customer-assignments' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.customer-assignments.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sales/visit-plans' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.visit-plans.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/operations/live-monitoring' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.live-monitoring.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/operations/monitoring' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.monitoring.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/operations/routing-quota' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.routing-quota.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/operations/delivery-orders' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.delivery-orders.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.delivery-orders.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/operations/routes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.routes.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.routes.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/operations/drivers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.drivers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/operations/delivery-orders-create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.delivery-orders.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/operations/routes/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.routes.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/reports' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.reports.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/reports/sales' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.reports.sales',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/reports/delivery' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.reports.delivery',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/reports/stock' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.reports.stock',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/reports/outstanding' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.reports.outstanding',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/reports/audit' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.reports.audit',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/finance/payments' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.payments.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/finance/settlements' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.settlements.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.settlements.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/finance/cash-ledgers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.cash-ledgers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.cash-ledgers.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/finance/settlements/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.settlements.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/finance/cash-ledgers/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.cash-ledgers.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sales-tasks' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales-tasks.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales-tasks.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sales-tasks/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales-tasks.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/admin/live-sales' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.admin.live-sales',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/task' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.task.show',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/tracking' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.tracking.show',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/map' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.map.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/tagging' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.tagging.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'sales.tagging.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/tagging/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.tagging.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/visits' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.visits.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'sales.visits.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/visits/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.visits.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/transactions/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.transactions.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/transactions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.transactions.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'sales.transactions.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/stock' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.stock.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/return-stock' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.return-stock.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'sales.return-stock.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sales/native-token' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.native-token',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sales/tasks/current' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tasks.current',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sales/tracking/start' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tracking.start',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sales/tracking/stop' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tracking.stop',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sales/tracking/status' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tracking.status',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sales/location' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.location',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sales/routes/today' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.routes.today',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sales/routes/reroute' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.routes.reroute',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/register' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'register',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::DutCAp2nEkaA43JR',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'login',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::f0gKFNXUlr5Sw9Qv',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/forgot-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.request',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'password.email',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/reset-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/verify-email' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.notice',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/email/verification-notification' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.send',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/confirm-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.confirm',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ozI1cg4nIyDIBtUj',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'logout',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 => 
    array (
      0 => '{^(?|/a(?|dmin/(?|master/(?|c(?|ompanies/([^/]++)(?|/edit(*:59)|(*:66))|ategories/([^/]++)(?|/edit(*:100)|(*:108))|ustomers/([^/]++)(?|(*:137)|/edit(*:150)|(*:158)))|branches/([^/]++)(?|/edit(*:193)|(*:201))|warehouses/([^/]++)(?|/edit(*:237)|(*:245))|units/([^/]++)(?|/edit(*:276)|(*:284))|pr(?|oducts/([^/]++)(?|/edit(*:321)|(*:329))|ices/([^/]++)(?|/edit(*:359)|(*:367)))|employees/([^/]++)(?|/edit(*:403)|(*:411))|s(?|ales/([^/]++)(?|/edit(*:445)|(*:453))|uppliers/([^/]++)(?|/edit(*:487)|(*:495)))|vehicles/([^/]++)(?|/edit(*:530)|(*:538)))|inventory/adjustments/([^/]++)(?|/apply(*:587)|(*:595))|distribution/(?|stock\\-requests/([^/]++)(?|(*:647)|/(?|submit(*:665)|cancel(*:679))|(*:688))|b(?|kb/([^/]++)(?|(*:715)|/(?|cancel(*:733)|apply(*:746)))|tb/([^/]++)(?|(*:770)|/(?|cancel(*:788)|apply(*:801)|discrepancy(*:820)))|ranch\\-transfer/([^/]++)(?|(*:857)|/(?|cancel(*:875)|send(*:887)|receive(?|(*:905))))))|sales(?|/(?|customer\\-(?|taggings/([^/]++)(?|(*:963)|/(?|approve(*:982)|reject(*:996)))|assignments/([^/]++)(?|/edit(*:1034)|(*:1043)))|visit(?|s/([^/]++)(*:1072)|\\-plans/([^/]++)(?|(*:1100)))|transactions/([^/]++)(*:1132)|invoices/([^/]++)(*:1158))|\\-tasks/([^/]++)(?|/(?|app(?|ly(*:1199)|rove\\-variance(*:1222))|cancel(*:1238)|reassign(*:1255))|(*:1265)))|operations/(?|d(?|elivery\\-orders/(?|([^/]++)(?|(*:1324)|/dispatch(*:1342))|bulk\\-dispatch(*:1366)|([^/]++)/(?|deliver(*:1394)|cancel(*:1409)))|rivers/([^/]++)/toggle(*:1442))|routes/([^/]++)(?|/edit(*:1475)|(*:1484)))|finance/(?|invoices/([^/]++)/payments(?|/create(*:1542)|(*:1551))|settlements/([^/]++)(?|/(?|edit(*:1592)|apply(*:1606))|(*:1616))|cash\\-ledgers/([^/]++)(*:1648)))|pi/(?|admin/sales/([^/]++)/locations(*:1695)|sales/(?|tasks/([^/]++)/(?|documents(?|(*:1743)|/([^/]++)/download(*:1770))|st(?|ock(*:1788)|art\\-work(*:1806))|verify\\-stock(*:1829))|route/customer/([^/]++)(*:1862))))|/s(?|ales/(?|map/([^/]++)(*:1899)|visits/([^/]++)/check\\-out(*:1934)|transactions/([^/]++)(*:1964)|invoices/([^/]++)/payments(?|/create(*:2009)|(*:2018)))|torage/(.*)(?|(*:2043)))|/reset\\-password/([^/]++)(*:2079)|/verify\\-email/([^/]++)/([^/]++)(*:2120))/?$}sDu',
    ),
    3 => 
    array (
      59 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.companies.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      66 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.companies.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.companies.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      100 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.categories.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      108 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.categories.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.categories.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      137 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.customers.show',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      150 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.customers.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      158 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.customers.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.customers.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      193 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.branches.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      201 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.branches.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.branches.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      237 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.warehouses.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      245 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.warehouses.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.warehouses.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      276 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.units.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      284 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.units.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.units.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      321 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.products.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      329 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.products.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.products.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      359 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.prices.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      367 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.prices.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.prices.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      403 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.employees.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      411 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.employees.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.employees.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      445 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.sales.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      453 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.sales.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.sales.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      487 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.suppliers.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      495 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.suppliers.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.suppliers.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      530 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.vehicles.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      538 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.vehicles.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.master.vehicles.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      587 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.inventory.adjustments.apply',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      595 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.inventory.adjustments.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      647 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.stock-requests.show',
          ),
          1 => 
          array (
            0 => 'stockRequest',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      665 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.stock-requests.submit',
          ),
          1 => 
          array (
            0 => 'stockRequest',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      679 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.stock-requests.cancel',
          ),
          1 => 
          array (
            0 => 'stockRequest',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      688 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.stock-requests.destroy',
          ),
          1 => 
          array (
            0 => 'stockRequest',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      715 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.bkb.show',
          ),
          1 => 
          array (
            0 => 'bkb',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      733 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.bkb.cancel',
          ),
          1 => 
          array (
            0 => 'bkb',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      746 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.bkb.apply',
          ),
          1 => 
          array (
            0 => 'bkb',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      770 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.btb.show',
          ),
          1 => 
          array (
            0 => 'btb',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      788 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.btb.cancel',
          ),
          1 => 
          array (
            0 => 'btb',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      801 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.btb.apply',
          ),
          1 => 
          array (
            0 => 'btb',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      820 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.btb.discrepancy',
          ),
          1 => 
          array (
            0 => 'btb',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      857 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.branch-transfer.show',
          ),
          1 => 
          array (
            0 => 'transfer',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      875 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.branch-transfer.cancel',
          ),
          1 => 
          array (
            0 => 'transfer',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      887 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.branch-transfer.send',
          ),
          1 => 
          array (
            0 => 'transfer',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      905 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.branch-transfer.receive-form',
          ),
          1 => 
          array (
            0 => 'transfer',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.distribution.branch-transfer.receive',
          ),
          1 => 
          array (
            0 => 'transfer',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      963 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.customer-taggings.show',
          ),
          1 => 
          array (
            0 => 'tagging',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      982 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.customer-taggings.approve',
          ),
          1 => 
          array (
            0 => 'tagging',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      996 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.customer-taggings.reject',
          ),
          1 => 
          array (
            0 => 'tagging',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1034 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.customer-assignments.edit',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1043 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.customer-assignments.update',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1072 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.visits.show',
          ),
          1 => 
          array (
            0 => 'visit',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1100 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.visit-plans.edit',
          ),
          1 => 
          array (
            0 => 'sales',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.visit-plans.update',
          ),
          1 => 
          array (
            0 => 'sales',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1132 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.transactions.show',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1158 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales.invoices.show',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1199 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales-tasks.apply',
          ),
          1 => 
          array (
            0 => 'salesTask',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1222 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales-tasks.approve-variance',
          ),
          1 => 
          array (
            0 => 'salesTask',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1238 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales-tasks.cancel',
          ),
          1 => 
          array (
            0 => 'salesTask',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1255 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales-tasks.reassign',
          ),
          1 => 
          array (
            0 => 'salesTask',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1265 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sales-tasks.show',
          ),
          1 => 
          array (
            0 => 'salesTask',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1324 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.delivery-orders.show',
          ),
          1 => 
          array (
            0 => 'deliveryOrder',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1342 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.delivery-orders.dispatch',
          ),
          1 => 
          array (
            0 => 'deliveryOrder',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1366 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.delivery-orders.bulk-dispatch',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1394 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.delivery-orders.deliver',
          ),
          1 => 
          array (
            0 => 'deliveryOrder',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1409 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.delivery-orders.cancel',
          ),
          1 => 
          array (
            0 => 'deliveryOrder',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1442 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.drivers.toggle',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1475 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.routes.edit',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1484 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.routes.update',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.operations.routes.destroy',
          ),
          1 => 
          array (
            0 => 'item',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1542 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.payments.create',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1551 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.payments.store',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1592 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.settlements.edit',
          ),
          1 => 
          array (
            0 => 'settlement',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1606 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.settlements.apply',
          ),
          1 => 
          array (
            0 => 'settlement',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1616 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.settlements.destroy',
          ),
          1 => 
          array (
            0 => 'settlement',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1648 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.finance.cash-ledgers.destroy',
          ),
          1 => 
          array (
            0 => 'cashLedger',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1695 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.admin.sales.locations',
          ),
          1 => 
          array (
            0 => 'sales',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1743 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tasks.documents',
          ),
          1 => 
          array (
            0 => 'task',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1770 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tasks.documents.download',
          ),
          1 => 
          array (
            0 => 'task',
            1 => 'document',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1788 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tasks.stock',
          ),
          1 => 
          array (
            0 => 'task',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1806 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tasks.start-work',
          ),
          1 => 
          array (
            0 => 'task',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1829 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.tasks.verify-stock',
          ),
          1 => 
          array (
            0 => 'task',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1862 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.sales.route.customer',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1899 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.map.show',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1934 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.visits.check-out',
          ),
          1 => 
          array (
            0 => 'visit',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1964 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.transactions.show',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2009 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.payments.create',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2018 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sales.payments.store',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2043 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local.upload',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2079 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.reset',
          ),
          1 => 
          array (
            0 => 'token',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2120 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.verify',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'hash',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' => 
  array (
    'sanctum.csrf-cookie' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sanctum/csrf-cookie',
      'action' => 
      array (
        'uses' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController@show',
        'controller' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController@show',
        'namespace' => NULL,
        'prefix' => 'sanctum',
        'where' => 
        array (
        ),
        'middleware' => 
        array (
          0 => 'web',
        ),
        'as' => 'sanctum.csrf-cookie',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::3r4u7gHVSNOHtClA' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'up',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1123:"function (\\Illuminate\\Http\\Request $request) {
                    $exception = null;

                    try {
                        \\Illuminate\\Support\\Facades\\Event::dispatch(new \\Illuminate\\Foundation\\Events\\DiagnosingHealth);
                    } catch (\\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    $status = $exception ? 500 : 200;

                    if ($request->expectsJson()) {
                        return response()->json([
                            \'status\' => $exception ? \'down\' : \'up\',
                        ], $status);
                    }

                    return response(\\Illuminate\\Support\\Facades\\View::file(\'D:\\\\Kerja\\\\POS-SALES\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\'.\'/../resources/health-up.blade.php\', [
                        \'exception\' => $exception,
                    ]), status: $status);
                }";s:5:"scope";s:54:"Illuminate\\Foundation\\Configuration\\ApplicationBuilder";s:4:"this";N;s:4:"self";s:32:"000000000000074c0000000000000000";}}',
        'as' => 'generated::3r4u7gHVSNOHtClA',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::zaqYOsPnBA9Jmccp' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:44:"function () {
    return \\view(\'welcome\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000074e0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::zaqYOsPnBA9Jmccp',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:311:"function () {
    $user = \\auth()->user();

    if ($user->hasRole(\'admin\')) {
        return \\redirect()->route(\'admin.dashboard\');
    }

    if ($user->hasRole(\'sales\')) {
        return \\redirect()->route(\'sales.dashboard\');
    }

    \\abort(403, \'Akun Anda belum memiliki role. Hubungi Administrator.\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000007520000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@edit',
        'controller' => 'App\\Http\\Controllers\\ProfileController@edit',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.edit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@update',
        'controller' => 'App\\Http\\Controllers\\ProfileController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@destroy',
        'controller' => 'App\\Http\\Controllers\\ProfileController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\DashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\DashboardController@index',
        'as' => 'admin.dashboard',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.companies.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/companies',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.companies.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.companies.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/companies/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.companies.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.companies.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/companies',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.companies.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.companies.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/companies/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.companies.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.companies.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/companies/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.companies.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.companies.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/companies/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.companies.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CompanyController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.branches.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/branches',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.branches.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.branches.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/branches/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.branches.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.branches.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/branches',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.branches.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.branches.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/branches/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.branches.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.branches.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/branches/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.branches.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.branches.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/branches/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.branches.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\BranchController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.warehouses.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/warehouses',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.warehouses.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.warehouses.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/warehouses/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.warehouses.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.warehouses.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/warehouses',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.warehouses.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.warehouses.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/warehouses/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.warehouses.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.warehouses.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/warehouses/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.warehouses.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.warehouses.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/warehouses/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.warehouses.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\WarehouseController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.categories.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.categories.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.categories.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/categories/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.categories.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.categories.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.categories.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.categories.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/categories/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.categories.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.categories.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/categories/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.categories.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.categories.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/categories/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.categories.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CategoryController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.units.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/units',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.units.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.units.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/units/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.units.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.units.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/units',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.units.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.units.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/units/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.units.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.units.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/units/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.units.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.units.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/units/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.units.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\UnitController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.products.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.products.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.products.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/products/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.products.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.products.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.products.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.products.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/products/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.products.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.products.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/products/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.products.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.products.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/products/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.products.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\ProductController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.prices.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/prices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.prices.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.prices.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/prices/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.prices.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.prices.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/prices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.prices.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.prices.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/prices/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.prices.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.prices.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/prices/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.prices.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.prices.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/prices/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.prices.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\PriceController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.employees.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/employees',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.employees.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.employees.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/employees/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.employees.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.employees.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/employees',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.employees.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.employees.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/employees/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.employees.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.employees.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/employees/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.employees.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.employees.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/employees/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.employees.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\EmployeeController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.sales.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/sales',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.sales.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.sales.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/sales/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.sales.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.sales.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/sales',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.sales.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.sales.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/sales/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.sales.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.sales.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/sales/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.sales.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.sales.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/sales/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.sales.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SalesController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.customers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/customers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.customers.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.customers.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/customers/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.customers.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.customers.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/customers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.customers.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.customers.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/customers/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.customers.show',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@show',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.customers.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/customers/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.customers.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.customers.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/customers/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.customers.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.customers.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/customers/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.customers.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\CustomerController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.vehicles.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/vehicles',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.vehicles.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.vehicles.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/vehicles/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.vehicles.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.vehicles.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/vehicles',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.vehicles.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.vehicles.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/vehicles/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.vehicles.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.vehicles.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/vehicles/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.vehicles.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.vehicles.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/vehicles/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.vehicles.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\VehicleController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.suppliers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/suppliers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.suppliers.index',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@index',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.suppliers.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/suppliers/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.suppliers.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@create',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.suppliers.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/master/suppliers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.suppliers.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@store',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.suppliers.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/master/suppliers/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.suppliers.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.suppliers.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/master/suppliers/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.suppliers.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@update',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.master.suppliers.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/master/suppliers/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:master-data.manage',
        ),
        'as' => 'admin.master.suppliers.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Master\\SupplierController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/master',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.inventory.stock.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/inventory/stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:inventory.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockController@index',
        'as' => 'admin.inventory.stock.index',
        'namespace' => NULL,
        'prefix' => 'admin/inventory',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.inventory.movements.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/inventory/movements',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:inventory.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockMovementController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockMovementController@index',
        'as' => 'admin.inventory.movements.index',
        'namespace' => NULL,
        'prefix' => 'admin/inventory',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.inventory.adjustments.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/inventory/adjustments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:inventory.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@index',
        'as' => 'admin.inventory.adjustments.index',
        'namespace' => NULL,
        'prefix' => 'admin/inventory',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.inventory.adjustments.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/inventory/adjustments/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:inventory.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@create',
        'as' => 'admin.inventory.adjustments.create',
        'namespace' => NULL,
        'prefix' => 'admin/inventory',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.inventory.adjustments.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/inventory/adjustments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:inventory.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@store',
        'as' => 'admin.inventory.adjustments.store',
        'namespace' => NULL,
        'prefix' => 'admin/inventory',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.inventory.adjustments.apply' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/inventory/adjustments/{item}/apply',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:inventory.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@apply',
        'controller' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@apply',
        'as' => 'admin.inventory.adjustments.apply',
        'namespace' => NULL,
        'prefix' => 'admin/inventory',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.inventory.adjustments.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/inventory/adjustments/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:inventory.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Inventory\\StockAdjustmentController@destroy',
        'as' => 'admin.inventory.adjustments.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/inventory',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.stock-requests.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/stock-requests',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@index',
        'as' => 'admin.distribution.stock-requests.index',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.stock-requests.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/stock-requests/{stockRequest}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@show',
        'as' => 'admin.distribution.stock-requests.show',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.bkb.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/bkb',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@index',
        'as' => 'admin.distribution.bkb.index',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.bkb.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/bkb/{bkb}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@show',
        'as' => 'admin.distribution.bkb.show',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.btb.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/btb',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@index',
        'as' => 'admin.distribution.btb.index',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.btb.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/btb/{btb}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@show',
        'as' => 'admin.distribution.btb.show',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.branch-transfer.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/branch-transfer',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@index',
        'as' => 'admin.distribution.branch-transfer.index',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.branch-transfer.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/branch-transfer/{transfer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@show',
        'as' => 'admin.distribution.branch-transfer.show',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.stock-requests.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/stock-requests-create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@create',
        'as' => 'admin.distribution.stock-requests.create',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.stock-requests.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/stock-requests',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@store',
        'as' => 'admin.distribution.stock-requests.store',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.stock-requests.submit' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/stock-requests/{stockRequest}/submit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@submit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@submit',
        'as' => 'admin.distribution.stock-requests.submit',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.stock-requests.cancel' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/stock-requests/{stockRequest}/cancel',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@cancel',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@cancel',
        'as' => 'admin.distribution.stock-requests.cancel',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.stock-requests.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/distribution/stock-requests/{stockRequest}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\StockRequestController@destroy',
        'as' => 'admin.distribution.stock-requests.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.bkb.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/bkb-create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@create',
        'as' => 'admin.distribution.bkb.create',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.bkb.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/bkb',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@store',
        'as' => 'admin.distribution.bkb.store',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.bkb.cancel' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/bkb/{bkb}/cancel',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@cancel',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@cancel',
        'as' => 'admin.distribution.bkb.cancel',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.btb.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/btb-create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@create',
        'as' => 'admin.distribution.btb.create',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.btb.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/btb',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@store',
        'as' => 'admin.distribution.btb.store',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.btb.cancel' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/btb/{btb}/cancel',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@cancel',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@cancel',
        'as' => 'admin.distribution.btb.cancel',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.branch-transfer.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/branch-transfer-create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@create',
        'as' => 'admin.distribution.branch-transfer.create',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.branch-transfer.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/branch-transfer',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@store',
        'as' => 'admin.distribution.branch-transfer.store',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.branch-transfer.cancel' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/branch-transfer/{transfer}/cancel',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@cancel',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@cancel',
        'as' => 'admin.distribution.branch-transfer.cancel',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.bkb.apply' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/bkb/{bkb}/apply',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.apply',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@apply',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BkbDistribusiController@apply',
        'as' => 'admin.distribution.bkb.apply',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.btb.apply' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/btb/{btb}/apply',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.apply',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@apply',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@apply',
        'as' => 'admin.distribution.btb.apply',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.btb.discrepancy' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/btb/{btb}/discrepancy',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.apply',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@discrepancy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BtbDistribusiController@discrepancy',
        'as' => 'admin.distribution.btb.discrepancy',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.branch-transfer.send' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/branch-transfer/{transfer}/send',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.apply',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@send',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@send',
        'as' => 'admin.distribution.branch-transfer.send',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.branch-transfer.receive-form' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/distribution/branch-transfer/{transfer}/receive',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.apply',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@receiveForm',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@receiveForm',
        'as' => 'admin.distribution.branch-transfer.receive-form',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.distribution.branch-transfer.receive' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/distribution/branch-transfer/{transfer}/receive',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:distribution.apply',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@receive',
        'controller' => 'App\\Http\\Controllers\\Admin\\Distribution\\BranchTransferController@receive',
        'as' => 'admin.distribution.branch-transfer.receive',
        'namespace' => NULL,
        'prefix' => 'admin/distribution',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.customer-taggings.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/customer-taggings',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerTaggingController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerTaggingController@index',
        'as' => 'admin.sales.customer-taggings.index',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.customer-taggings.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/customer-taggings/{tagging}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerTaggingController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerTaggingController@show',
        'as' => 'admin.sales.customer-taggings.show',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.visits.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/visits',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitController@index',
        'as' => 'admin.sales.visits.index',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.visits.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/visits/{visit}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitController@show',
        'as' => 'admin.sales.visits.show',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.transactions.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/transactions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\SalesTransactionController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\SalesTransactionController@index',
        'as' => 'admin.sales.transactions.index',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.transactions.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/transactions/{transaction}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\SalesTransactionController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\SalesTransactionController@show',
        'as' => 'admin.sales.transactions.show',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.invoices.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/invoices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\InvoiceController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\InvoiceController@index',
        'as' => 'admin.sales.invoices.index',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.invoices.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/invoices/{invoice}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\InvoiceController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\InvoiceController@show',
        'as' => 'admin.sales.invoices.show',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.customer-taggings.approve' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/sales/customer-taggings/{tagging}/approve',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerTaggingController@approve',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerTaggingController@approve',
        'as' => 'admin.sales.customer-taggings.approve',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.customer-taggings.reject' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/sales/customer-taggings/{tagging}/reject',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-management.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerTaggingController@reject',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerTaggingController@reject',
        'as' => 'admin.sales.customer-taggings.reject',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.customer-assignments.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/customer-assignments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:customer-assignment.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerAssignmentController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerAssignmentController@index',
        'as' => 'admin.sales.customer-assignments.index',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.visit-plans.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/visit-plans',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:customer-assignment.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitPlanController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitPlanController@index',
        'as' => 'admin.sales.visit-plans.index',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.visit-plans.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/visit-plans/{sales}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:customer-assignment.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitPlanController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitPlanController@edit',
        'as' => 'admin.sales.visit-plans.edit',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.customer-assignments.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales/customer-assignments/{customer}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:customer-assignment.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerAssignmentController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerAssignmentController@edit',
        'as' => 'admin.sales.customer-assignments.edit',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.customer-assignments.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/sales/customer-assignments/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:customer-assignment.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerAssignmentController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\CustomerAssignmentController@update',
        'as' => 'admin.sales.customer-assignments.update',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales.visit-plans.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/sales/visit-plans/{sales}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:customer-assignment.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitPlanController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Sales\\VisitPlanController@update',
        'as' => 'admin.sales.visit-plans.update',
        'namespace' => NULL,
        'prefix' => 'admin/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.live-monitoring.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/live-monitoring',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:live-monitoring.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\LiveSalesController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\LiveSalesController@index',
        'as' => 'admin.operations.live-monitoring.index',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.monitoring.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/monitoring',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\MonitoringController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\MonitoringController@index',
        'as' => 'admin.operations.monitoring.index',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.routing-quota.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/routing-quota',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RoutingQuotaController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\RoutingQuotaController@index',
        'as' => 'admin.operations.routing-quota.index',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.delivery-orders.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/delivery-orders',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@index',
        'as' => 'admin.operations.delivery-orders.index',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.delivery-orders.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/delivery-orders/{deliveryOrder}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@show',
        'as' => 'admin.operations.delivery-orders.show',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.routes.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/routes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@index',
        'as' => 'admin.operations.routes.index',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.drivers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/drivers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DriverController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DriverController@index',
        'as' => 'admin.operations.drivers.index',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.delivery-orders.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/delivery-orders-create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@create',
        'as' => 'admin.operations.delivery-orders.create',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.delivery-orders.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/operations/delivery-orders',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@store',
        'as' => 'admin.operations.delivery-orders.store',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.delivery-orders.dispatch' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/operations/delivery-orders/{deliveryOrder}/dispatch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@dispatch',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@dispatch',
        'as' => 'admin.operations.delivery-orders.dispatch',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.delivery-orders.bulk-dispatch' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/operations/delivery-orders/bulk-dispatch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@bulkDispatch',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@bulkDispatch',
        'as' => 'admin.operations.delivery-orders.bulk-dispatch',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.delivery-orders.deliver' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/operations/delivery-orders/{deliveryOrder}/deliver',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@deliver',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@deliver',
        'as' => 'admin.operations.delivery-orders.deliver',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.delivery-orders.cancel' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/operations/delivery-orders/{deliveryOrder}/cancel',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@cancel',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryOrderController@cancel',
        'as' => 'admin.operations.delivery-orders.cancel',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.routes.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/routes/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'as' => 'admin.operations.routes.create',
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@create',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.routes.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/operations/routes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'as' => 'admin.operations.routes.store',
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@store',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.routes.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/operations/routes/{item}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'as' => 'admin.operations.routes.edit',
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.routes.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/operations/routes/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'as' => 'admin.operations.routes.update',
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@update',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.routes.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/operations/routes/{item}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'as' => 'admin.operations.routes.destroy',
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DeliveryRouteController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.operations.drivers.toggle' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/operations/drivers/{item}/toggle',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:operations.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\DriverController@toggle',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\DriverController@toggle',
        'as' => 'admin.operations.drivers.toggle',
        'namespace' => NULL,
        'prefix' => 'admin/operations',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.reports.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reports',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:reports.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\ReportController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\ReportController@index',
        'as' => 'admin.reports.index',
        'namespace' => NULL,
        'prefix' => 'admin/reports',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.reports.sales' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reports/sales',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:reports.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\ReportController@sales',
        'controller' => 'App\\Http\\Controllers\\Admin\\ReportController@sales',
        'as' => 'admin.reports.sales',
        'namespace' => NULL,
        'prefix' => 'admin/reports',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.reports.delivery' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reports/delivery',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:reports.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\ReportController@delivery',
        'controller' => 'App\\Http\\Controllers\\Admin\\ReportController@delivery',
        'as' => 'admin.reports.delivery',
        'namespace' => NULL,
        'prefix' => 'admin/reports',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.reports.stock' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reports/stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:reports.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\ReportController@stock',
        'controller' => 'App\\Http\\Controllers\\Admin\\ReportController@stock',
        'as' => 'admin.reports.stock',
        'namespace' => NULL,
        'prefix' => 'admin/reports',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.reports.outstanding' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reports/outstanding',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:reports.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\ReportController@outstanding',
        'controller' => 'App\\Http\\Controllers\\Admin\\ReportController@outstanding',
        'as' => 'admin.reports.outstanding',
        'namespace' => NULL,
        'prefix' => 'admin/reports',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.reports.audit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reports/audit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:reports.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\ReportController@audit',
        'controller' => 'App\\Http\\Controllers\\Admin\\ReportController@audit',
        'as' => 'admin.reports.audit',
        'namespace' => NULL,
        'prefix' => 'admin/reports',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.payments.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/finance/payments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\PaymentController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\PaymentController@index',
        'as' => 'admin.finance.payments.index',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.settlements.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/finance/settlements',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@index',
        'as' => 'admin.finance.settlements.index',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.cash-ledgers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/finance/cash-ledgers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\CashLedgerController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\CashLedgerController@index',
        'as' => 'admin.finance.cash-ledgers.index',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.payments.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/finance/invoices/{invoice}/payments/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\PaymentController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\PaymentController@create',
        'as' => 'admin.finance.payments.create',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.payments.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/finance/invoices/{invoice}/payments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\PaymentController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\PaymentController@store',
        'as' => 'admin.finance.payments.store',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.settlements.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/finance/settlements/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@create',
        'as' => 'admin.finance.settlements.create',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.settlements.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/finance/settlements',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@store',
        'as' => 'admin.finance.settlements.store',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.settlements.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/finance/settlements/{settlement}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@edit',
        'as' => 'admin.finance.settlements.edit',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.settlements.apply' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/finance/settlements/{settlement}/apply',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@apply',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@apply',
        'as' => 'admin.finance.settlements.apply',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.settlements.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/finance/settlements/{settlement}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\SettlementController@destroy',
        'as' => 'admin.finance.settlements.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.cash-ledgers.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/finance/cash-ledgers/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\CashLedgerController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\CashLedgerController@create',
        'as' => 'admin.finance.cash-ledgers.create',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.cash-ledgers.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/finance/cash-ledgers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\CashLedgerController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\CashLedgerController@store',
        'as' => 'admin.finance.cash-ledgers.store',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.finance.cash-ledgers.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/finance/cash-ledgers/{cashLedger}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:finance.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Finance\\CashLedgerController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\Finance\\CashLedgerController@destroy',
        'as' => 'admin.finance.cash-ledgers.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/finance',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales-tasks.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales-tasks',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-task.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@index',
        'as' => 'admin.sales-tasks.index',
        'namespace' => NULL,
        'prefix' => 'admin/sales-tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales-tasks.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales-tasks/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-task.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@create',
        'as' => 'admin.sales-tasks.create',
        'namespace' => NULL,
        'prefix' => 'admin/sales-tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales-tasks.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/sales-tasks',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-task.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@store',
        'as' => 'admin.sales-tasks.store',
        'namespace' => NULL,
        'prefix' => 'admin/sales-tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales-tasks.apply' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/sales-tasks/{salesTask}/apply',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-task.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@apply',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@apply',
        'as' => 'admin.sales-tasks.apply',
        'namespace' => NULL,
        'prefix' => 'admin/sales-tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales-tasks.cancel' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/sales-tasks/{salesTask}/cancel',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-task.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@cancel',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@cancel',
        'as' => 'admin.sales-tasks.cancel',
        'namespace' => NULL,
        'prefix' => 'admin/sales-tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales-tasks.approve-variance' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/sales-tasks/{salesTask}/approve-variance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-task.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@approveVariance',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@approveVariance',
        'as' => 'admin.sales-tasks.approve-variance',
        'namespace' => NULL,
        'prefix' => 'admin/sales-tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales-tasks.reassign' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/sales-tasks/{salesTask}/reassign',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-task.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@reassignSales',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@reassignSales',
        'as' => 'admin.sales-tasks.reassign',
        'namespace' => NULL,
        'prefix' => 'admin/sales-tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sales-tasks.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sales-tasks/{salesTask}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:sales-task.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@show',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\SalesTaskController@show',
        'as' => 'admin.sales-tasks.show',
        'namespace' => NULL,
        'prefix' => 'admin/sales-tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.admin.live-sales' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/admin/live-sales',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:live-monitoring.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\LiveSalesController@liveSales',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\LiveSalesController@liveSales',
        'as' => 'api.admin.live-sales',
        'namespace' => NULL,
        'prefix' => '/api/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.admin.sales.locations' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/admin/sales/{sales}/locations',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:admin',
          4 => 'permission:live-monitoring.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\Operations\\LiveSalesController@locations',
        'controller' => 'App\\Http\\Controllers\\Admin\\Operations\\LiveSalesController@locations',
        'as' => 'api.admin.sales.locations',
        'namespace' => NULL,
        'prefix' => '/api/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\DashboardController@index',
        'controller' => 'App\\Http\\Controllers\\Sales\\DashboardController@index',
        'as' => 'sales.dashboard',
        'namespace' => NULL,
        'prefix' => '/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.task.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/task',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:sales-task.view',
        ),
        'uses' => '\\App\\Http\\Controllers\\Sales\\TaskPageController@show',
        'controller' => '\\App\\Http\\Controllers\\Sales\\TaskPageController@show',
        'as' => 'sales.task.show',
        'namespace' => NULL,
        'prefix' => '/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.tracking.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/tracking',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:tracking.manage',
        ),
        'uses' => '\\App\\Http\\Controllers\\Sales\\TrackingPageController@show',
        'controller' => '\\App\\Http\\Controllers\\Sales\\TrackingPageController@show',
        'as' => 'sales.tracking.show',
        'namespace' => NULL,
        'prefix' => '/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.map.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/map',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:customer.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\MapController@index',
        'controller' => 'App\\Http\\Controllers\\Sales\\MapController@index',
        'as' => 'sales.map.index',
        'namespace' => NULL,
        'prefix' => 'sales/map',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.map.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/map/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:customer.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\MapController@show',
        'controller' => 'App\\Http\\Controllers\\Sales\\MapController@show',
        'as' => 'sales.map.show',
        'namespace' => NULL,
        'prefix' => 'sales/map',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.tagging.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/tagging',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:tagging-toko.manage',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\CustomerTaggingController@index',
        'controller' => 'App\\Http\\Controllers\\Sales\\CustomerTaggingController@index',
        'as' => 'sales.tagging.index',
        'namespace' => NULL,
        'prefix' => 'sales/tagging',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.tagging.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/tagging/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:tagging-toko.manage',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\CustomerTaggingController@create',
        'controller' => 'App\\Http\\Controllers\\Sales\\CustomerTaggingController@create',
        'as' => 'sales.tagging.create',
        'namespace' => NULL,
        'prefix' => 'sales/tagging',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.tagging.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'sales/tagging',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:tagging-toko.manage',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\CustomerTaggingController@store',
        'controller' => 'App\\Http\\Controllers\\Sales\\CustomerTaggingController@store',
        'as' => 'sales.tagging.store',
        'namespace' => NULL,
        'prefix' => 'sales/tagging',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.visits.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/visits',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:kunjungan.manage',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\VisitController@index',
        'controller' => 'App\\Http\\Controllers\\Sales\\VisitController@index',
        'as' => 'sales.visits.index',
        'namespace' => NULL,
        'prefix' => 'sales/visits',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.visits.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/visits/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:kunjungan.manage',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\VisitController@create',
        'controller' => 'App\\Http\\Controllers\\Sales\\VisitController@create',
        'as' => 'sales.visits.create',
        'namespace' => NULL,
        'prefix' => 'sales/visits',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.visits.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'sales/visits',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:kunjungan.manage',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\VisitController@store',
        'controller' => 'App\\Http\\Controllers\\Sales\\VisitController@store',
        'as' => 'sales.visits.store',
        'namespace' => NULL,
        'prefix' => 'sales/visits',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.visits.check-out' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'sales/visits/{visit}/check-out',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:kunjungan.manage',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\VisitController@checkOut',
        'controller' => 'App\\Http\\Controllers\\Sales\\VisitController@checkOut',
        'as' => 'sales.visits.check-out',
        'namespace' => NULL,
        'prefix' => 'sales/visits',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.transactions.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/transactions/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:sales-transaction.create',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TransactionController@create',
        'controller' => 'App\\Http\\Controllers\\Sales\\TransactionController@create',
        'as' => 'sales.transactions.create',
        'namespace' => NULL,
        'prefix' => 'sales/transactions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.transactions.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'sales/transactions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:sales-transaction.create',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TransactionController@store',
        'controller' => 'App\\Http\\Controllers\\Sales\\TransactionController@store',
        'as' => 'sales.transactions.store',
        'namespace' => NULL,
        'prefix' => 'sales/transactions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.transactions.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/transactions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:sales-transaction.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TransactionController@index',
        'controller' => 'App\\Http\\Controllers\\Sales\\TransactionController@index',
        'as' => 'sales.transactions.index',
        'namespace' => NULL,
        'prefix' => 'sales/transactions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.transactions.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/transactions/{transaction}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:sales-transaction.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TransactionController@show',
        'controller' => 'App\\Http\\Controllers\\Sales\\TransactionController@show',
        'as' => 'sales.transactions.show',
        'namespace' => NULL,
        'prefix' => 'sales/transactions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.stock.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:sales-stock.view',
          5 => 'active_sales_task',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\StockController@index',
        'controller' => 'App\\Http\\Controllers\\Sales\\StockController@index',
        'as' => 'sales.stock.index',
        'namespace' => NULL,
        'prefix' => '/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.return-stock.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/return-stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:sales-stock.return',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\ReturnStockController@index',
        'controller' => 'App\\Http\\Controllers\\Sales\\ReturnStockController@index',
        'as' => 'sales.return-stock.index',
        'namespace' => NULL,
        'prefix' => 'sales/return-stock',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.return-stock.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'sales/return-stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:sales-stock.return',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\ReturnStockController@store',
        'controller' => 'App\\Http\\Controllers\\Sales\\ReturnStockController@store',
        'as' => 'sales.return-stock.store',
        'namespace' => NULL,
        'prefix' => 'sales/return-stock',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.payments.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sales/invoices/{invoice}/payments/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:payment.create',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\PaymentController@create',
        'controller' => 'App\\Http\\Controllers\\Sales\\PaymentController@create',
        'as' => 'sales.payments.create',
        'namespace' => NULL,
        'prefix' => 'sales/invoices/{invoice}/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.payments.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'sales/invoices/{invoice}/payments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
          4 => 'permission:payment.create',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\PaymentController@store',
        'controller' => 'App\\Http\\Controllers\\Sales\\PaymentController@store',
        'as' => 'sales.payments.store',
        'namespace' => NULL,
        'prefix' => 'sales/invoices/{invoice}/payments',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sales.native-token' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'sales/native-token',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'role:sales',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\NativeTokenController@store',
        'controller' => 'App\\Http\\Controllers\\Sales\\NativeTokenController@store',
        'as' => 'sales.native-token',
        'namespace' => NULL,
        'prefix' => '/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tasks.current' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/sales/tasks/current',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:sales-task.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TaskController@current',
        'controller' => 'App\\Http\\Controllers\\Sales\\TaskController@current',
        'as' => 'api.sales.tasks.current',
        'namespace' => NULL,
        'prefix' => 'api/sales/tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tasks.documents' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/sales/tasks/{task}/documents',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:sales-task.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TaskController@documents',
        'controller' => 'App\\Http\\Controllers\\Sales\\TaskController@documents',
        'as' => 'api.sales.tasks.documents',
        'namespace' => NULL,
        'prefix' => 'api/sales/tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tasks.documents.download' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sales/tasks/{task}/documents/{document}/download',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:sales-task.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TaskController@markDocumentDownloaded',
        'controller' => 'App\\Http\\Controllers\\Sales\\TaskController@markDocumentDownloaded',
        'as' => 'api.sales.tasks.documents.download',
        'namespace' => NULL,
        'prefix' => 'api/sales/tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tasks.stock' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/sales/tasks/{task}/stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:sales-task.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TaskController@stock',
        'controller' => 'App\\Http\\Controllers\\Sales\\TaskController@stock',
        'as' => 'api.sales.tasks.stock',
        'namespace' => NULL,
        'prefix' => 'api/sales/tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tasks.verify-stock' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sales/tasks/{task}/verify-stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:sales-task.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TaskController@verifyStock',
        'controller' => 'App\\Http\\Controllers\\Sales\\TaskController@verifyStock',
        'as' => 'api.sales.tasks.verify-stock',
        'namespace' => NULL,
        'prefix' => 'api/sales/tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tasks.start-work' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sales/tasks/{task}/start-work',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:sales-task.view',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TaskController@startWork',
        'controller' => 'App\\Http\\Controllers\\Sales\\TaskController@startWork',
        'as' => 'api.sales.tasks.start-work',
        'namespace' => NULL,
        'prefix' => 'api/sales/tasks',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tracking.start' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sales/tracking/start',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:tracking.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TrackingController@start',
        'controller' => 'App\\Http\\Controllers\\Sales\\TrackingController@start',
        'as' => 'api.sales.tracking.start',
        'namespace' => NULL,
        'prefix' => 'api/sales/tracking',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tracking.stop' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sales/tracking/stop',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:tracking.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TrackingController@stop',
        'controller' => 'App\\Http\\Controllers\\Sales\\TrackingController@stop',
        'as' => 'api.sales.tracking.stop',
        'namespace' => NULL,
        'prefix' => 'api/sales/tracking',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.tracking.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/sales/tracking/status',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:tracking.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\TrackingController@status',
        'controller' => 'App\\Http\\Controllers\\Sales\\TrackingController@status',
        'as' => 'api.sales.tracking.status',
        'namespace' => NULL,
        'prefix' => 'api/sales/tracking',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.location' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sales/location',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:tracking.manage',
        ),
        'uses' => '\\App\\Http\\Controllers\\Sales\\TrackingController@location',
        'controller' => '\\App\\Http\\Controllers\\Sales\\TrackingController@location',
        'as' => 'api.sales.location',
        'namespace' => NULL,
        'prefix' => '/api/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.route.customer' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sales/route/customer/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:customer.view',
        ),
        'uses' => '\\App\\Http\\Controllers\\Sales\\RouteController@calculate',
        'controller' => '\\App\\Http\\Controllers\\Sales\\RouteController@calculate',
        'as' => 'api.sales.route.customer',
        'namespace' => NULL,
        'prefix' => '/api/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.routes.today' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/sales/routes/today',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:tracking.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\RouteController@today',
        'controller' => 'App\\Http\\Controllers\\Sales\\RouteController@today',
        'as' => 'api.sales.routes.today',
        'namespace' => NULL,
        'prefix' => '/api/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.sales.routes.reroute' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sales/routes/reroute',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful',
          2 => 'auth:sanctum',
          3 => 'verified',
          4 => 'role:sales',
          5 => 'permission:tracking.manage',
        ),
        'uses' => 'App\\Http\\Controllers\\Sales\\RouteController@reroute',
        'controller' => 'App\\Http\\Controllers\\Sales\\RouteController@reroute',
        'as' => 'api.sales.routes.reroute',
        'namespace' => NULL,
        'prefix' => '/api/sales',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'register' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'register',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::DutCAp2nEkaA43JR' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::DutCAp2nEkaA43JR',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'login' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'login',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::f0gKFNXUlr5Sw9Qv' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::f0gKFNXUlr5Sw9Qv',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.request' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.request',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.email' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.email',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.reset' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reset-password/{token}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.reset',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'reset-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.notice' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'verify-email',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\EmailVerificationPromptController@__invoke',
        'controller' => 'App\\Http\\Controllers\\Auth\\EmailVerificationPromptController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.notice',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.verify' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'verify-email/{id}/{hash}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'signed',
          3 => 'throttle:6,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\VerifyEmailController@__invoke',
        'controller' => 'App\\Http\\Controllers\\Auth\\VerifyEmailController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.verify',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.send' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'email/verification-notification',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'throttle:6,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\EmailVerificationNotificationController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\EmailVerificationNotificationController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.send',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.confirm' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'confirm-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@show',
        'controller' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.confirm',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::ozI1cg4nIyDIBtUj' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'confirm-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::ozI1cg4nIyDIBtUj',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordController@update',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'logout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@destroy',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'logout',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:38:"D:\\Kerja\\POS-SALES\\storage\\app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:323:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ServeFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000007540000000000000000";}}',
        'as' => 'storage.local',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local.upload' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:38:"D:\\Kerja\\POS-SALES\\storage\\app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:325:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ReceiveFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000008380000000000000000";}}',
        'as' => 'storage.local.upload',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
