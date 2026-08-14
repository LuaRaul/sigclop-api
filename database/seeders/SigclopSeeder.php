<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Department;
use App\Models\Distribution;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\InternalRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RequestItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SigclopSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | DEPARTAMENTOS
            |--------------------------------------------------------------------------
            */

            $departments = collect([
                [
                    'code' => 'DIR-GERAL',
                    'name' => 'Direcção Geral',
                    'description' => 'Direcção Geral do SIGCLOP.',
                    'active' => true,
                ],
                [
                    'code' => 'ADM',
                    'name' => 'Administração',
                    'description' => 'Departamento Administrativo.',
                    'active' => true,
                ],
                [
                    'code' => 'LOG',
                    'name' => 'Logística',
                    'description' => 'Departamento de Logística e Armazém.',
                    'active' => true,
                ],
                [
                    'code' => 'RH',
                    'name' => 'Recursos Humanos',
                    'description' => 'Departamento de Recursos Humanos.',
                    'active' => true,
                ],
                [
                    'code' => 'VIG',
                    'name' => 'Vigilância',
                    'description' => 'Departamento de Vigilância.',
                    'active' => true,
                ],
            ])->map(function ($department) {
                return Department::updateOrCreate(
                    [
                        'code' => $department['code'],
                    ],
                    $department
                );
            });

            $departmentGeral = $departments->firstWhere(
                'code',
                'DIR-GERAL'
            );

            $departmentAdmin = $departments->firstWhere(
                'code',
                'ADM'
            );

            $departmentLogistica = $departments->firstWhere(
                'code',
                'LOG'
            );

            $departmentRH = $departments->firstWhere(
                'code',
                'RH'
            );

            /*
            |--------------------------------------------------------------------------
            | UTILIZADORES
            |--------------------------------------------------------------------------
            */

            $directorGeral = User::updateOrCreate(
                [
                    'email' => 'director.geral@sigclop.local',
                ],
                [
                    'name' => 'Director Geral',
                    'department_id' => $departmentGeral->id,
                    'password' => Hash::make('12345678'),
                    'active' => true,
                ]
            );

            $directorGeral->syncRoles([
                'director_geral',
            ]);

            $director = User::updateOrCreate(
                [
                    'email' => 'director@sigclop.local',
                ],
                [
                    'name' => 'Director de Logística',
                    'department_id' => $departmentLogistica->id,
                    'password' => Hash::make('12345678'),
                    'active' => true,
                ]
            );

            $director->syncRoles([
                'director',
            ]);

            $funcionario = User::updateOrCreate(
                [
                    'email' => 'funcionario@sigclop.local',
                ],
                [
                    'name' => 'Funcionário Administrativo',
                    'department_id' => $departmentAdmin->id,
                    'password' => Hash::make('12345678'),
                    'active' => true,
                ]
            );

            $funcionario->syncRoles([
                'funcionario',
            ]);

            $funcionarioArmazem = User::updateOrCreate(
                [
                    'email' => 'armazem@sigclop.local',
                ],
                [
                    'name' => 'Funcionário do Armazém',
                    'department_id' => $departmentLogistica->id,
                    'password' => Hash::make('12345678'),
                    'active' => true,
                ]
            );

            $funcionarioArmazem->syncRoles([
                'funcionario_armazem',
            ]);

            /*
            |--------------------------------------------------------------------------
            | CATEGORIAS DE DOCUMENTOS
            |--------------------------------------------------------------------------
            */

            $documentCategories = collect([
                [
                    'code' => 'OFICIO',
                    'name' => 'Ofícios',
                    'description' => 'Ofícios administrativos.',
                    'active' => true,
                ],
                [
                    'code' => 'RELATORIO',
                    'name' => 'Relatórios',
                    'description' => 'Relatórios institucionais.',
                    'active' => true,
                ],
                [
                    'code' => 'MEMORANDO',
                    'name' => 'Memorandos',
                    'description' => 'Memorandos internos.',
                    'active' => true,
                ],
                [
                    'code' => 'DESPACHO',
                    'name' => 'Despachos',
                    'description' => 'Despachos administrativos.',
                    'active' => true,
                ],
            ])->map(function ($category) {
                return DocumentCategory::updateOrCreate(
                    [
                        'code' => $category['code'],
                    ],
                    $category
                );
            });

            /*
            |--------------------------------------------------------------------------
            | CATEGORIAS DE PRODUTOS
            |--------------------------------------------------------------------------
            */

            $productCategories = collect([
                [
                    'code' => 'LIMPEZA',
                    'name' => 'Material de Limpeza',
                    'description' => 'Produtos de limpeza e higiene.',
                    'active' => true,
                ],
                [
                    'code' => 'ESCRITORIO',
                    'name' => 'Material de Escritório',
                    'description' => 'Material de escritório.',
                    'active' => true,
                ],
                [
                    'code' => 'HIGIENE',
                    'name' => 'Higiene',
                    'description' => 'Produtos de higiene pessoal.',
                    'active' => true,
                ],
                [
                    'code' => 'ALIMENTACAO',
                    'name' => 'Alimentação',
                    'description' => 'Produtos alimentares.',
                    'active' => true,
                ],
            ])->map(function ($category) {
                return ProductCategory::updateOrCreate(
                    [
                        'code' => $category['code'],
                    ],
                    $category
                );
            });

            /*
            |--------------------------------------------------------------------------
            | FORNECEDORES
            |--------------------------------------------------------------------------
            */

            $suppliers = collect([
                [
                    'name' => 'Fornecedor Geral SIGCLOP',
                    'nif' => '500000000',
                    'phone' => '900000000',
                    'email' => 'fornecedor@sigclop.local',
                    'address' => 'Luanda',
                    'active' => true,
                ],
                [
                    'name' => 'Fornecedor de Material de Escritório',
                    'nif' => '500000001',
                    'phone' => '900000001',
                    'email' => 'escritorio@sigclop.local',
                    'address' => 'Luanda',
                    'active' => true,
                ],
                [
                    'name' => 'Fornecedor de Higiene e Limpeza',
                    'nif' => '500000002',
                    'phone' => '900000002',
                    'email' => 'limpeza@sigclop.local',
                    'address' => 'Luanda',
                    'active' => true,
                ],
            ])->map(function ($supplier) {
                return Supplier::updateOrCreate(
                    [
                        'name' => $supplier['name'],
                    ],
                    $supplier
                );
            });

            $supplier = $suppliers->first();

            /*
            |--------------------------------------------------------------------------
            | PRODUTOS
            |--------------------------------------------------------------------------
            */

            $productsData = [
                [
                    'category' => 'LIMPEZA',
                    'name' => 'Sabão',
                    'sku' => 'LIM-001',
                    'barcode' => '900000000001',
                    'unit' => 'unidade',
                    'quantity' => 100,
                    'minimum_quantity' => 20,
                    'maximum_quantity' => 300,
                    'estimated_unit_cost' => 500,
                    'location' => 'Armazém A - A01',
                    'description' => 'Sabão para distribuição interna.',
                ],
                [
                    'category' => 'LIMPEZA',
                    'name' => 'Detergente',
                    'sku' => 'LIM-002',
                    'barcode' => '900000000002',
                    'unit' => 'litro',
                    'quantity' => 80,
                    'minimum_quantity' => 15,
                    'maximum_quantity' => 200,
                    'estimated_unit_cost' => 1200,
                    'location' => 'Armazém A - A02',
                    'description' => 'Detergente líquido.',
                ],
                [
                    'category' => 'LIMPEZA',
                    'name' => 'Vassoura',
                    'sku' => 'LIM-003',
                    'barcode' => '900000000003',
                    'unit' => 'unidade',
                    'quantity' => 40,
                    'minimum_quantity' => 10,
                    'maximum_quantity' => 100,
                    'estimated_unit_cost' => 2500,
                    'location' => 'Armazém A - A03',
                    'description' => 'Vassoura para limpeza.',
                ],
                [
                    'category' => 'ESCRITORIO',
                    'name' => 'Resma de Papel A4',
                    'sku' => 'ESC-001',
                    'barcode' => '900000000004',
                    'unit' => 'resma',
                    'quantity' => 60,
                    'minimum_quantity' => 10,
                    'maximum_quantity' => 150,
                    'estimated_unit_cost' => 6500,
                    'location' => 'Armazém B - B01',
                    'description' => 'Papel A4 para utilização administrativa.',
                ],
                [
                    'category' => 'ESCRITORIO',
                    'name' => 'Caneta Azul',
                    'sku' => 'ESC-002',
                    'barcode' => '900000000005',
                    'unit' => 'unidade',
                    'quantity' => 200,
                    'minimum_quantity' => 30,
                    'maximum_quantity' => 500,
                    'estimated_unit_cost' => 150,
                    'location' => 'Armazém B - B02',
                    'description' => 'Caneta esferográfica azul.',
                ],
                [
                    'category' => 'HIGIENE',
                    'name' => 'Papel Higiénico',
                    'sku' => 'HIG-001',
                    'barcode' => '900000000006',
                    'unit' => 'pacote',
                    'quantity' => 120,
                    'minimum_quantity' => 25,
                    'maximum_quantity' => 300,
                    'estimated_unit_cost' => 1800,
                    'location' => 'Armazém C - C01',
                    'description' => 'Papel higiénico.',
                ],
                [
                    'category' => 'ALIMENTACAO',
                    'name' => 'Água Mineral',
                    'sku' => 'ALI-001',
                    'barcode' => '900000000007',
                    'unit' => 'caixa',
                    'quantity' => 50,
                    'minimum_quantity' => 10,
                    'maximum_quantity' => 100,
                    'estimated_unit_cost' => 3500,
                    'location' => 'Armazém C - C02',
                    'description' => 'Água mineral engarrafada.',
                ],
            ];

            $products = collect();

            foreach ($productsData as $productData) {

                $category = $productCategories->firstWhere(
                    'code',
                    $productData['category']
                );

                if (!$category) {
                    continue;
                }

                $product = Product::updateOrCreate(
                    [
                        'sku' => $productData['sku'],
                    ],
                    [
                        'product_category_id' => $category->id,
                        'supplier_id' => $supplier?->id,
                        'name' => $productData['name'],
                        'barcode' => $productData['barcode'],
                        'unit' => $productData['unit'],
                        'quantity' => $productData['quantity'],
                        'minimum_quantity' => $productData['minimum_quantity'],
                        'maximum_quantity' => $productData['maximum_quantity'],
                        'estimated_unit_cost' => $productData['estimated_unit_cost'],
                        'location' => $productData['location'],
                        'active' => true,
                        'description' => $productData['description'],
                    ]
                );

                $products->push($product);

                /*
                |--------------------------------------------------------------------------
                | Movimento inicial
                |--------------------------------------------------------------------------
                */

                $movementExists = StockMovement::query()
                    ->where(
                        'product_id',
                        $product->id
                    )
                    ->where(
                        'reference',
                        'STOCK-SEED-' . $product->sku
                    )
                    ->exists();

                if (
                    !$movementExists
                    && $productData['quantity'] > 0
                ) {

                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $directorGeral->id,
                        'type' => 'entrada',
                        'quantity' => $productData['quantity'],
                        'balance_before' => 0,
                        'balance_after' => $productData['quantity'],
                        'reference' => 'STOCK-SEED-' . $product->sku,
                        'notes' => 'Stock inicial criado pelo seeder.',
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | DOCUMENTOS
            |--------------------------------------------------------------------------
            */

            $oficio = $documentCategories->firstWhere(
                'code',
                'OFICIO'
            );

            Document::updateOrCreate(
                [
                    'reference_number' => 'DOC-SEED-001',
                ],
                [
                    'document_category_id' => $oficio?->id,
                    'department_id' => $departmentAdmin->id,
                    'user_id' => $directorGeral->id,
                    'title' => 'Ofício Administrativo de Exemplo',
                    'document_date' => now()->toDateString(),
                    'status' => 'arquivado',
                    'file_path' => null,
                    'summary' => 'Documento de exemplo criado pelo seeder.',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | CONFIGURAÇÕES
            |--------------------------------------------------------------------------
            */

            $settings = [
                [
                    'key' => 'app_name',
                    'value' => 'SIGCLOP',
                    'group' => 'general',
                    'type' => 'text',
                    'label' => 'Nome da aplicação',
                    'description' => 'Nome apresentado na aplicação.',
                    'is_public' => true,
                    'editable' => true,
                    'updated_by' => $directorGeral->id,
                ],

                [
                    'key' => 'app_description',
                    'value' => 'Sistema Integrado de Gestão e Controlo Logístico Prisional',
                    'group' => 'general',
                    'type' => 'text',
                    'label' => 'Descrição da aplicação',
                    'description' => 'Descrição institucional do SIGCLOP.',
                    'is_public' => true,
                    'editable' => true,
                    'updated_by' => $directorGeral->id,
                ],

                [
                    'key' => 'default_stock_minimum',
                    'value' => '10',
                    'group' => 'stock',
                    'type' => 'number',
                    'label' => 'Stock mínimo padrão',
                    'description' => 'Quantidade mínima padrão utilizada para alertas de stock.',
                    'is_public' => false,
                    'editable' => true,
                    'updated_by' => $directorGeral->id,
                ],
            ];

            foreach ($settings as $setting) {
                Setting::updateOrCreate(
                    [
                        'key' => $setting['key'],
                    ],
                    $setting
                );
            }

            /*
            |--------------------------------------------------------------------------
            | PEDIDO INTERNO
            |--------------------------------------------------------------------------
            */

            $firstProduct = $products->first();

            if ($firstProduct) {

                $internalRequest = InternalRequest::updateOrCreate(
                    [
                        'request_number' => 'REQ-SEED-001',
                    ],
                    [
                        'department_id' => $departmentAdmin->id,
                        'requested_by' => $funcionario->id,
                        'assigned_approver_id' => $director->id,
                        'priority' => 'normal',
                        'status' => 'aprovado',
                        'justification' => 'Pedido de material para utilização interna.',
                        'needed_at' => now()->addDays(7),
                        'submitted_at' => now(),
                        'approved_at' => now(),
                        'completed_at' => null,
                        'notes' => 'Pedido criado pelo seeder.',
                    ]
                );

                $requestItem = RequestItem::updateOrCreate(
                    [
                        'internal_request_id' => $internalRequest->id,
                        'product_id' => $firstProduct->id,
                    ],
                    [
                        'description' => $firstProduct->name,
                        'quantity_requested' => 20,
                        'quantity_approved' => 20,
                        'notes' => 'Item de exemplo.',
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | APROVAÇÃO
                |--------------------------------------------------------------------------
                */

                Approval::updateOrCreate(
                    [
                        'internal_request_id' => $internalRequest->id,
                        'approved_by' => $director->id,
                    ],
                    [
                        'approval_level' => 1,
                        'decision' => 'aprovado',
                        'comment' => 'Pedido aprovado para distribuição.',
                        'decided_at' => now(),
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | DISTRIBUIÇÃO
                |--------------------------------------------------------------------------
                |
                | A distribuição de exemplo não é criada automaticamente
                | para evitar reduzir novamente o stock sempre que o
                | seeder for executado.
                |
                */
            }

            /*
            |--------------------------------------------------------------------------
            | ACTIVIDADE
            |--------------------------------------------------------------------------
            */

            ActivityLog::updateOrCreate(
                [
                    'action' => 'Executou seeder SIGCLOP',
                    'entity' => 'Seeder',
                ],
                [
                    'user_id' => $directorGeral->id,
                    'entity_id' => null,
                    'metadata' => [
                        'seeded_at' => now()->toDateTimeString(),
                        'environment' => app()->environment(),
                    ],
                ]
            );
        });
    }
}
