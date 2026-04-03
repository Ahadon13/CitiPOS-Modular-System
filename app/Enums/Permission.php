<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    // --- Admin Specific ---
    case AdminDashboard = 'admin-dashboard';
    case ManageBranches = 'manage-branches';
    case ManageUsers = 'manage-users';
    case AdminReports = 'admin-reports';

    // --- Pharmacy & Grocery Specific ---
    case StoreDashboard = 'store-dashboard';
    case ManageTransactions = 'manage-transactions';
    case ManageProducts = 'manage-products';
    case ManageStocks = 'manage-stocks';
    case ManagePurchases = 'manage-purchases';
    case ManageExpenses = 'manage-expenses';
    case ManageCustomers = 'manage-customers';
    case StoreReports = 'store-reports';
    case AccessPos = 'access-pos';

    /**
     * @return array<string, list<self>>
     */
    public static function groupedPermissions(): array
    {
        return [
            'Administration' => [
                self::AdminDashboard,
                self::ManageBranches,
                self::ManageUsers,
                self::AdminReports,
            ],
            'Pharmacy & Grocery Operations' => [
                self::StoreDashboard,
                self::AccessPos,
                self::ManageTransactions,
                self::ManageProducts,
                self::ManageStocks,
                self::ManagePurchases,
                self::ManageExpenses,
                self::ManageCustomers,
                self::StoreReports,
            ],
        ];
    }

    public function label(): string
    {
        return match ($this) {
            // Admin Labels
            self::AdminDashboard => 'Access Admin Dashboard',
            self::ManageBranches => 'Manage Branches',
            self::ManageUsers => 'Manage Users',
            self::AdminReports => 'View Admin Reports',

            // Store Labels
            self::StoreDashboard => 'Access Store Dashboard',
            self::AccessPos => 'Access Point of Sale (POS)',
            self::ManageTransactions => 'Manage Transactions',
            self::ManageProducts => 'Manage Products',
            self::ManageStocks => 'Manage Stocks',
            self::ManagePurchases => 'Manage Purchase Orders',
            self::ManageExpenses => 'Manage Expenses',
            self::ManageCustomers => 'Manage Customers',
            self::StoreReports => 'View Store Reports',
        };
    }
}
