<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpa cache das permissões
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
    |--------------------------------------------------------------------------
    | PERMISSÕES
    |--------------------------------------------------------------------------
    */

        $permissions = [

            /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
            'dashboard.nacional',
            'dashboard.regional',
            'dashboard.local',

            /*
    |--------------------------------------------------------------------------
    | UTILIZADORES
    |--------------------------------------------------------------------------
    */
            'utilizadores.visualizar',
            'utilizadores.criar',
            'utilizadores.editar',
            'utilizadores.eliminar',

            /*
    |--------------------------------------------------------------------------
    | PERFIS
    |--------------------------------------------------------------------------
    */
            'perfis.visualizar',
            'perfis.criar',
            'perfis.editar',
            'perfis.eliminar',

            /*
    |--------------------------------------------------------------------------
    | SECTORES
    |--------------------------------------------------------------------------
    */
            'sectores.visualizar',
            'sectores.criar',
            'sectores.editar',
            'sectores.eliminar',

            /*
    |--------------------------------------------------------------------------
    | DOCUMENTOS
    |--------------------------------------------------------------------------
    */
            'documentos.visualizar',
            'documentos.criar',
            'documentos.editar',
            'documentos.eliminar',

            /*
    |--------------------------------------------------------------------------
    | UTENSÍLIOS / MATERIAIS
    |--------------------------------------------------------------------------
    */
            'utensilios.visualizar',
            'utensilios.criar',
            'utensilios.editar',
            'utensilios.eliminar',

            /*
    |--------------------------------------------------------------------------
    | ARMAZÉM
    |--------------------------------------------------------------------------
    */
            'armazem.visualizar_stock',
            'armazem.entrada_stock',
            'armazem.saida_stock',
            'armazem.ajustar_stock',
            'armazem.inventario',

            /*
    |--------------------------------------------------------------------------
    | MOVIMENTAÇÕES
    |--------------------------------------------------------------------------
    */
            'movimentacoes.visualizar',
            'movimentacoes.registar',

            /*
    |--------------------------------------------------------------------------
    | PEDIDOS
    |--------------------------------------------------------------------------
    */
            'pedidos.visualizar',
            'pedidos.criar',
            'pedidos.editar',
            'pedidos.eliminar',
            'pedidos.reencaminhar',
            'pedidos.aprovar',
            'pedidos.rejeitar',
            'pedidos.levantar',
            'pedidos.concluir',

            /*
    |--------------------------------------------------------------------------
    | DISTRIBUIÇÃO
    |--------------------------------------------------------------------------
    */
            'distribuicao.visualizar',
            'distribuicao.criar',
            'distribuicao.editar',
            'distribuicao.aprovar',
            'distribuicao.entregar',
            'distribuicao.cancelar',
            'distribuicao.eliminar',
            'distribuicao.plano',

            /*
    |--------------------------------------------------------------------------
    | RELATÓRIOS
    |--------------------------------------------------------------------------
    */
            'relatorios.visualizar',
            'relatorios.exportar',

            /*
    |--------------------------------------------------------------------------
    | AUDITORIA
    |--------------------------------------------------------------------------
    */
            'auditoria.visualizar',

            /*
    |--------------------------------------------------------------------------
    | CONFIGURAÇÕES
    |--------------------------------------------------------------------------
    */
            'configuracoes.visualizar',
            'configuracoes.editar',

            /*
    |--------------------------------------------------------------------------
    | FORNECEDORES
    |--------------------------------------------------------------------------
    */
            'fornecedores.visualizar',
            'fornecedores.criar',
            'fornecedores.editar',
            'fornecedores.eliminar',

            /*
    |--------------------------------------------------------------------------
    | EFECTIVOS
    |--------------------------------------------------------------------------
    */
            'efectivos.visualizar',

            /*
    |--------------------------------------------------------------------------
    | PRISIONEIROS
    |--------------------------------------------------------------------------
    */
            'prisioneiros.visualizar',

        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | PERFIS
    |--------------------------------------------------------------------------
    */

        $directorGeral = Role::firstOrCreate([
            'name' => 'director_geral',
            'guard_name' => 'web'
        ]);

        $director = Role::firstOrCreate([
            'name' => 'director',
            'guard_name' => 'web'
        ]);

        $funcionario = Role::firstOrCreate([
            'name' => 'funcionario',
            'guard_name' => 'web'
        ]);

        $funcionarioArmazem = Role::firstOrCreate([
            'name' => 'funcionario_armazem',
            'guard_name' => 'web'
        ]);

        /*
    |--------------------------------------------------------------------------
    | ATRIBUIÇÃO DAS PERMISSÕES
    |--------------------------------------------------------------------------
    */

        // Director Geral
        $directorGeral->syncPermissions(Permission::all());

        // Director
        $director->syncPermissions([

            'dashboard.regional',

            'pedidos.visualizar',
            'pedidos.aprovar',
            'pedidos.rejeitar',
            'pedidos.reencaminhar',

            'documentos.visualizar',

            'utensilios.visualizar',

            'armazem.visualizar_stock',

            'distribuicao.visualizar',
            'distribuicao.plano',

            'relatorios.visualizar',

            'sectores.visualizar',

            'efectivos.visualizar',

            'prisioneiros.visualizar',

        ]);

        // Funcionário
        $funcionario->syncPermissions([

            'dashboard.local',

            'pedidos.criar',
            'pedidos.visualizar',
            'pedidos.editar',
            'pedidos.reencaminhar',
            'pedidos.levantar',

            'documentos.visualizar',

            'utensilios.visualizar',

        ]);
        // Funcionário Armazém
        $funcionarioArmazem->syncPermissions([

            'dashboard.local',

            'utensilios.visualizar',
            'utensilios.criar',
            'utensilios.editar',

            'sectores.visualizar',

            'armazem.visualizar_stock',
            'armazem.entrada_stock',
            'armazem.saida_stock',
            'armazem.inventario',

            'movimentacoes.visualizar',
            'movimentacoes.registar',

            'distribuicao.visualizar',
            'distribuicao.criar',
            'distribuicao.editar',
            'distribuicao.entregar',

            'pedidos.visualizar',
            'pedidos.reencaminhar',

        ]);
    }
}
