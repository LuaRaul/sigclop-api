# Gestão de Perfis, Permissões e Acessos — SIGCLOP

## Acesso inicial
A gestão de perfis/permissões é reservada no backend ao email `admin@sigclop.local`. O frontend também oculta a opção para os restantes utilizadores, mas a protecção real é aplicada na API.

## Funcionalidades
- CRUD de perfis/funções (`roles`).
- CRUD de permissões (`permissions`).
- Associar/remover permissões de um perfil.
- Associar vários perfis e permissões directas a cada utilizador.
- Registo de auditoria das alterações.
- O perfil `administrador` não pode ser eliminado ou renomeado.
- Não é permitido eliminar perfis que ainda estejam associados a utilizadores.
- `admin@sigclop.local` mantém todas as permissões actuais e futuras e não pode perder o acesso de administrador através do ecrã de gestão.

## Endpoints
Todos requerem `auth:sanctum` e são exclusivos do administrador do sistema:
- `GET /api/access-management`
- `GET|POST /api/access-management/roles`
- `PUT|DELETE /api/access-management/roles/{role}`
- `GET|POST /api/access-management/permissions`
- `PUT|DELETE /api/access-management/permissions/{permission}`
- `GET|PUT /api/access-management/users/{user}`

## Aplicação
1. Copiar os ficheiros do ZIP para o projecto, preservando o `.env`.
2. No backend: `composer dump-autoload` e `php artisan optimize:clear`.
3. Não é necessária uma nova migration: o projecto já usa as tabelas Spatie `roles`, `permissions` e pivots.
4. Garantir que `admin@sigclop.local` existe e que o seeder `RolesAndPermissionsSeeder` já foi executado.
5. No frontend: `npm install` e `npm run dev`.
6. Entrar com `admin@sigclop.local` e abrir **Perfis e Permissões** na barra lateral.

Nota: criar uma permissão na aplicação cria o registo Spatie e permite associá-lo a perfis/utilizadores. Para proteger uma nova rota/ação, a API também tem de usar a permissão no middleware/controller correspondente.
