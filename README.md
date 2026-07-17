# Vitrine SaaS

Fundação de uma plataforma SaaS multiloja em Laravel 13. Esta etapa inclui autenticação, onboarding da primeira loja, seleção segura da loja atual e painel responsivo. Produtos, pedidos, pagamentos, frete e loja pública ainda não fazem parte do escopo.

## Requisitos

- PHP 8.3+ com extensões PDO e PostgreSQL
- Composer 2
- Node.js 22+ e npm
- PostgreSQL 16+ (ou Docker com Compose)

Stack: Laravel 13, Livewire 4, Blade, Alpine.js, Tailwind CSS 4, Vite 8 e PHPUnit 12.

## Instalação local

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
php artisan migrate
npm run build
php artisan serve
```

Edite `.env` para apontar ao PostgreSQL. Os valores padrão usam banco `vitrine`, usuário `vitrine`, senha local `local_password`, host `127.0.0.1` e porta `5432`. Nunca reutilize essa senha fora do desenvolvimento.

Para desenvolvimento do frontend, execute `npm run dev`. Para testes, `php artisan test`. O formatter é `vendor/bin/pint`.

## Docker Compose

```bash
docker compose build
docker compose up -d postgres
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate
docker compose up app
```

O app fica em `http://localhost:8000`; o PostgreSQL é publicado em `localhost:5432`. O frontend pode ser executado no host com `npm install && npm run dev`.

## Arquitetura multiloja

`User` e `Store` têm relação muitos-para-muitos por `store_user`, cuja chave primária composta impede duplicidade. A função do membro (`owner`, `admin`, `attendant` ou `inventory`) fica no pivô. Cada loja tem exatamente um `StoreSetting`.

O serviço `CurrentStore` armazena apenas o ID selecionado na sessão e resolve a loja explicitamente. Não há Global Scope oculto: futuros modelos comerciais devem receber/consultar o contexto e sempre aplicar `where('store_id', $currentStore->get()->id)`. Essa decisão torna o isolamento visível e testável.

O middleware `store.member` exige que o usuário autenticado pertença à loja da sessão. A `StorePolicy` protege visualização, edição e gestão de equipe no servidor, inclusive contra IDs adulterados. O seletor usa model binding e autorização antes de trocar a sessão.

## Fluxo da loja atual

1. Cadastro autentica o usuário e abre `/onboarding/store`.
2. `CreateStore` usa uma transação para criar loja, configurações e vínculo `owner`.
3. A loja é gravada como atual na sessão e o usuário segue para `/painel`.
4. No login, a primeira loja vinculada é selecionada; quem ainda não tem loja vai ao onboarding.
5. A troca em `/painel/selecionar-loja/{store}` só ocorre após autorização da policy.

## Rotas e testes

Use `php artisan route:list` para consultar as rotas nomeadas. A suíte cobre autenticação, onboarding, vínculo de proprietário, dashboard, slug único, troca de loja, adulteração de sessão/URL e policies.

```bash
php artisan test
```
