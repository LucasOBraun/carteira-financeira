# Carteira Financeira

Aplicação full-stack de carteira digital com cadastro, autenticação, depósitos, transferências e estorno de operações.

## Stack

- **Backend:** Laravel 13 (PHP 8.3), MySQL 8, Laravel Sanctum
- **Frontend:** Vue 3, Vite, Pinia, Vue Router, Axios
- **Infra:** Docker Compose (Windows e Linux)

## Requisitos atendidos

- Cadastro e autenticação de usuários
- Depósito, envio e recebimento de valores
- Validação de saldo antes de transferências
- Depósito soma ao saldo atual (inclusive quando negativo)
- Estorno de depósitos e transferências (inconsistência via rollback transacional ou solicitação do usuário)
- Idempotência em depósitos e transferências

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/macOS) **ou** Docker Engine + Docker Compose (Linux)
- Git

## Primeiro uso

### 1. Clonar o repositório

```bash
git clone <url-do-repositorio>
cd desafio-full-stack
```

### 2. Configurar variáveis de ambiente do backend

```bash
cp backend/.env.example backend/.env
```

No Windows (PowerShell):

```powershell
Copy-Item backend\.env.example backend\.env
```

> O `docker-compose.yml` já injeta as variáveis de banco e Sanctum nos containers. O `.env` é usado pelo Laravel dentro do container.

### 3. Subir o ambiente

```bash
docker compose up --build -d
```

Na primeira execução o container `app` irá:

1. Instalar dependências PHP (`composer install`)
2. Gerar `APP_KEY`
3. Executar migrations

### 4. (Opcional) Popular usuários demo

```bash
docker compose exec app php artisan db:seed
```

Usuários criados:

| E-mail | Senha | Saldo inicial |
|--------|-------|---------------|
| alice@demo.com | password123 | R$ 500,00 |
| bob@demo.com | password123 | R$ 200,00 |

### 5. Acessar a aplicação

| Serviço | URL |
|---------|-----|
| Frontend (Vue) | http://localhost:5173 |
| API (Nginx/Laravel) | http://localhost:8080 |
| MySQL | localhost:3306 |

## Comandos úteis

```bash
# Ver logs
docker compose logs -f

# Parar containers
docker compose down

# Parar e remover volumes (reset do banco)
docker compose down -v

# Rodar migrations manualmente
docker compose exec app php artisan migrate

# Rodar testes
docker compose exec app php artisan test

# Acessar shell do container PHP
docker compose exec app sh
```

## Endpoints da API

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| POST | `/api/register` | Não | Cadastro + criação de carteira |
| POST | `/api/login` | Não | Login (sessão Sanctum) |
| POST | `/api/logout` | Sim | Logout |
| GET | `/api/user` | Sim | Usuário autenticado |
| GET | `/api/wallet` | Sim | Saldo da carteira |
| GET | `/api/wallet/transactions` | Sim | Extrato paginado |
| POST | `/api/wallet/deposit` | Sim | Depósito |
| POST | `/api/wallet/transfer` | Sim | Transferência |
| POST | `/api/wallet/transactions/{id}/reverse` | Sim | Estorno |

### Exemplo de depósito

```bash
curl -X POST http://localhost:8080/api/wallet/deposit \
  -H "Content-Type: application/json" \
  -H "Cookie: ..." \
  -d '{"amount":"100.00","idempotency_key":"uuid-unico"}'
```

### Formato de erro

```json
{
  "message": "Saldo insuficiente para transferência.",
  "error_code": "INSUFFICIENT_BALANCE"
}
```

## Arquitetura

```
frontend/          Vue 3 SPA
backend/           API Laravel
  app/Actions/     Operações financeiras (Deposit, Transfer, Reverse)
  app/Services/    Orquestração com transações DB
  app/Repositories Acesso a dados com lock pessimista
docker/            Configuração Nginx
```

### Fluxo financeiro

1. Controller valida entrada (Form Request)
2. `WalletService` abre transação DB
3. Action aplica regras de negócio com `lockForUpdate` na carteira
4. Lançamentos imutáveis em `ledger_entries`
5. Saldo atualizado com `bcmath` (precisão decimal)
6. Estorno gera operação inversa e marca original como `reversed`

### Decisões técnicas

- **Ledger + saldo cacheado:** histórico auditável + consulta rápida de saldo
- **Idempotency key:** evita duplicidade em retries de rede
- **Sanctum SPA:** cookies httpOnly + CSRF para segurança no browser
- **Repository interface:** facilita testes e inversão de dependência (SOLID)

## Testes

```bash
docker compose exec app php artisan test
```

Cobertura básica:

- **Unit:** depósito com saldo negativo, transferência sem saldo, estorno duplo
- **Feature:** auth, depósito, transferência, estorno

## Troubleshooting

### Porta 8080 ou 5173 em uso

Altere as portas no `docker-compose.yml`:

```yaml
ports:
  - "8081:80"   # nginx
  - "5174:5173" # frontend
```

Atualize também `FRONTEND_URL` e `SANCTUM_STATEFUL_DOMAINS` no compose/backend `.env`.

### Permissões no Linux

Se o Laravel não conseguir escrever em `storage/` ou `bootstrap/cache/`:

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Windows: performance de volumes

Se o backend estiver lento, use WSL2 como backend do Docker Desktop e clone o projeto dentro do filesystem Linux (`\\wsl$\...`).

### Frontend não conecta na API

1. Confirme que `VITE_API_URL=http://localhost:8080` está no serviço `frontend` do compose
2. Verifique se `SANCTUM_STATEFUL_DOMAINS` inclui `localhost:5173`
3. Acesse http://localhost:8080/up — deve retornar status healthy

### Erro de migration na primeira subida

Aguarde o MySQL ficar healthy e rode:

```bash
docker compose exec app php artisan migrate --force
```

### Reset completo

```bash
docker compose down -v
docker compose up --build -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
```

## Estrutura do repositório

```
desafio-full-stack/
├── backend/              Laravel API
├── frontend/             Vue 3 SPA
├── docker/
│   └── nginx/
├── docker-compose.yml
└── README.md
```

## Licença

Projeto desenvolvido para fins de avaliação técnica.
