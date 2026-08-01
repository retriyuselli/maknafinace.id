<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResourceEnum: string implements HasLabel
{
    case AccountManagerTargetResource = 'AccountManagerTargetResource';
    case BankReconciliationResource = 'BankReconciliationResource';
    case BankStatementResource = 'BankStatementResource';
    case BlogResource = 'BlogResource';
    case CategoryResource = 'CategoryResource';
    case ChartOfAccountResource = 'ChartOfAccountResource';
    case CompanyResource = 'CompanyResource';
    case CompanyLogoResource = 'CompanyLogoResource';
    case DataPembayaranResource = 'DataPembayaranResource';
    case DataPribadiResource = 'DataPribadiResource';
    case DocumentCategoryResource = 'DocumentCategoryResource';
    case DocumentationCategoryResource = 'DocumentationCategoryResource';
    case DocumentationResource = 'DocumentationResource';
    case DocumentResource = 'DocumentResource';
    case EmployeeResource = 'EmployeeResource';
    case ExpenseOpResource = 'ExpenseOpResource';
    case ExpenseResource = 'ExpenseResource';
    case FixedAssetResource = 'FixedAssetResource';
    case IndustryResource = 'IndustryResource';
    case JournalBatchResource = 'JournalBatchResource';
    case LeaveBalanceResource = 'LeaveBalanceResource';
    case LeaveRequestResource = 'LeaveRequestResource';
    case LeaveTypeResource = 'LeaveTypeResource';
    case NotaDinasResource = 'NotaDinasResource';
    case NotaDinasDetailResource = 'NotaDinasDetailResource';
    case OrderResource = 'OrderResource';
    case PaymentMethodResource = 'PaymentMethodResource';
    case PayrollResource = 'PayrollResource';
    case PembayaranPiutangResource = 'PembayaranPiutangResource';
    case PendapatanLainResource = 'PendapatanLainResource';
    case PengeluaranLainResource = 'PengeluaranLainResource';
    case PiutangResource = 'PiutangResource';
    case ProductResource = 'ProductResource';
    case ProspectAppResource = 'ProspectAppResource';
    case ProspectResource = 'ProspectResource';
    case SimulasiProdukResource = 'SimulasiProdukResource';
    case SopCategoryResource = 'SopCategoryResource';
    case SopResource = 'SopResource';
    case StatusResource = 'StatusResource';
    case UserResource = 'UserResource';
    case VendorResource = 'VendorResource';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AccountManagerTargetResource => 'Account Manager Target',
            self::BankReconciliationResource => 'Bank Reconciliation',
            self::BankStatementResource => 'Bank Statement',
            self::BlogResource => 'Blog',
            self::CategoryResource => 'Category',
            self::ChartOfAccountResource => 'Chart Of Account',
            self::CompanyResource => 'Company',
            self::CompanyLogoResource => 'Company Logo',
            self::DataPembayaranResource => 'Data Pembayaran',
            self::DataPribadiResource => 'Data Pribadi',
            self::DocumentCategoryResource => 'Document Category',
            self::DocumentationCategoryResource => 'Documentation Category',
            self::DocumentationResource => 'Documentation',
            self::DocumentResource => 'Document',
            self::EmployeeResource => 'Employee',
            self::ExpenseOpResource => 'Expense Ops',
            self::ExpenseResource => 'Expense',
            self::FixedAssetResource => 'Fixed Asset',
            self::IndustryResource => 'Industry',
            self::JournalBatchResource => 'Journal Batch',
            self::LeaveBalanceResource => 'Leave Balance',
            self::LeaveRequestResource => 'Leave Request',
            self::LeaveTypeResource => 'Leave Type',
            self::NotaDinasResource => 'Nota Dinas',
            self::NotaDinasDetailResource => 'Nota Dinas Detail',
            self::OrderResource => 'Order',
            self::PaymentMethodResource => 'Payment Method',
            self::PayrollResource => 'Payroll',
            self::PembayaranPiutangResource => 'Pembayaran Piutang',
            self::PendapatanLainResource => 'Pendapatan Lain',
            self::PengeluaranLainResource => 'Pengeluaran Lain',
            self::PiutangResource => 'Piutang',
            self::ProductResource => 'Product',
            self::ProspectAppResource => 'Prospect App',
            self::ProspectResource => 'Prospect',
            self::SimulasiProdukResource => 'Simulasi Produk',
            self::SopCategoryResource => 'SOP Category',
            self::SopResource => 'SOP',
            self::StatusResource => 'Status',
            self::UserResource => 'User',
            self::VendorResource => 'Vendor',
        };
    }
}
