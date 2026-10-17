# Adequação do SIGCLOP ao modelo dos Serviços Prisionais

## Funcionalidades alinhadas ao PDF `IMPUTS APLICAÇÃO.pdf`
- Administrador: papel separado para gestão de utilizadores/perfis e acessos; conta de demonstração `admin@sigclop.local` (senha inicial `12345678`, alterar após primeiro login).
- Funcionário/solicitante: pedidos classificados como Vestuário, Artigos de higiene ou Meios de oficina.
- Director/Director Geral: consulta e decisão dos pedidos, com aprovação/rejeição e monitorização; o Director Geral deixa de herdar permissões de administração de contas.
- Funcionário de armazém: registo/gestão de materiais, stock e distribuição.

## Alterações técnicas
- Nova coluna `internal_requests.request_type` (migração aditiva, default `higiene` para compatibilidade com registos existentes).
- Validação API limitada a `vestuario`, `higiene`, `meios_oficina`.
- Resposta do modelo inclui `request_type_label`.
- Seeder inclui categorias `VESTUARIO` e `MEIOS_OFICINA`, mantendo categorias antigas.
- Frontend inclui o tipo de pedido no formulário e na listagem.
- Sidebar usa permissões reais `sectores.visualizar` e mostra Dashboard aos três níveis de acesso.

## Aplicação
1. Fazer backup da base de dados e dos dois projectos.
2. Na API: `php artisan migrate`
3. Opcional: `php artisan db:seed --class=SigclopSeeder` para inserir/actualizar categorias de materiais sem apagar dados.
4. Limpar cache: `php artisan optimize:clear`
5. No frontend: `npm install && npm run build` (ou `npm run dev`).

Não executar `migrate:fresh` em ambientes com dados reais.
