<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\InternalRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SigclopSeeder extends Seeder
{
    public function run(): void
    {
        $departments = collect([
            ['name' => 'Direccao Prisional', 'code' => 'DIR'],
            ['name' => 'Logistica', 'code' => 'LOG'],
            ['name' => 'Armazem Central', 'code' => 'ARM'],
            ['name' => 'Saude Prisional', 'code' => 'SAU'],
            ['name' => 'Seguranca', 'code' => 'SEG'],
        ])->map(fn ($department) => Department::firstOrCreate(['code' => $department['code']], $department));

        $admin = User::firstOrCreate(
            ['email' => 'admin@sigclop.local'],
            [
                'name' => 'Administrador SIGCLOP',
                'role' => 'administrador',
                'department_id' => $departments->firstWhere('code', 'DIR')->id,
                'password' => Hash::make('password'),
            ]
        );

        $director = User::firstOrCreate(
            ['email' => 'director@sigclop.local'],
            [
                'name' => 'Director Prisional',
                'role' => 'director',
                'department_id' => $departments->firstWhere('code', 'DIR')->id,
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'logistica@sigclop.local'],
            [
                'name' => 'Maria Santos',
                'role' => 'logistica',
                'department_id' => $departments->firstWhere('code', 'LOG')->id,
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'armazem@sigclop.local'],
            [
                'name' => 'Carlos Alberto',
                'role' => 'armazem',
                'department_id' => $departments->firstWhere('code', 'ARM')->id,
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'auditor@sigclop.local'],
            [
                'name' => 'Ana Ferreira',
                'role' => 'auditor',
                'department_id' => $departments->firstWhere('code', 'DIR')->id,
                'password' => Hash::make('password'),
            ]
        );

        $documentCategory = DocumentCategory::firstOrCreate(
            ['code' => 'REQ'],
            ['name' => 'Requisicoes internas', 'description' => 'Pedidos e memorandos de requisicao de materiais.']
        );

        $productCategory = ProductCategory::firstOrCreate(
            ['code' => 'HIG'],
            ['name' => 'Higiene', 'description' => 'Produtos de higiene e limpeza institucional.']
        );

        $supplier = Supplier::firstOrCreate(
            ['name' => 'Fornecedor Geral'],
            ['phone' => '+244 000 000 000', 'active' => true]
        );

        $product = Product::firstOrCreate(
            ['sku' => 'HIG-SAB-001'],
            [
                'product_category_id' => $productCategory->id,
                'supplier_id' => $supplier->id,
                'name' => 'Sabao em barra',
                'unit' => 'unidade',
                'quantity' => 120,
                'minimum_quantity' => 30,
                'estimated_unit_cost' => 250,
            ]
        );

        Document::firstOrCreate(
            ['reference_number' => 'DOC-001'],
            [
                'document_category_id' => $documentCategory->id,
                'department_id' => $departments->firstWhere('code', 'LOG')->id,
                'user_id' => $admin->id,
                'title' => 'Procedimento de controlo logistico',
                'document_date' => now()->toDateString(),
                'status' => 'arquivado',
                'summary' => 'Documento inicial para organizar o fluxo logistico prisional.',
            ]
        );

        $request = InternalRequest::firstOrCreate(
            ['request_number' => 'REQ-DEMO-001'],
            [
                'department_id' => $departments->firstWhere('code', 'SAU')->id,
                'requested_by' => $admin->id,
                'assigned_approver_id' => $director->id,
                'priority' => 'normal',
                'status' => 'pendente',
                'justification' => 'Reposicao de materiais de higiene para uso interno.',
            ]
        );
        $request->update(['assigned_approver_id' => $request->assigned_approver_id ?: $director->id]);

        $request->items()->firstOrCreate(
            ['product_id' => $product->id],
            ['quantity_requested' => 20, 'description' => 'Sabao para distribuicao interna.']
        );

        InternalRequest::whereNull('assigned_approver_id')->update(['assigned_approver_id' => $director->id]);

        collect([
            ['key' => 'app_name', 'value' => 'SIGCLOP', 'group' => 'geral', 'type' => 'text'],
            ['key' => 'institution_name', 'value' => 'Servico Prisional', 'group' => 'geral', 'type' => 'text'],
            ['key' => 'low_stock_notifications', 'value' => '1', 'group' => 'notificacoes', 'type' => 'boolean'],
            ['key' => 'approval_required', 'value' => '1', 'group' => 'fluxo', 'type' => 'boolean'],
        ])->each(fn ($setting) => Setting::firstOrCreate(['key' => $setting['key']], $setting));
    }
}
