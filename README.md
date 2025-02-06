# Sistema de API Laravel

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

## Descrição

Este sistema é uma API desenvolvida em Laravel que permite:

- Controle de ponto por meio de biometria utilizando o leitor Fingertech Hamster DX USB.
- - Sistema de login com controle de roles e permissions.
- CRUD de localidade, unidade e funcionários.
- Geração de relatórios de presença dos funcionários.
- Inserção de dias não úteis.

## Requisitos

Certifique-se de ter os seguintes requisitos instalados:

- PHP >= 8.0
- Composer
- Banco de dados (MySQL, PostgreSQL, ou outro suportado pelo Laravel)
- Leitor Biométrico Fingertech Hamster DX USB e seu driver

## Instalação

1. Clone o repositório:

   ```bash
   git clone https://github.com/JeffersonCarvalhoReis/gestao-ponto-digital-backend.git
   cd gestao-ponto-digital-backend
   ```

2. Instale as dependências do projeto:

   ```bash
   composer install
   ```

3. Copie o arquivo `.env.example` para `.env`:

   ```bash
   cp .env.example .env
   ```

4. Configure o arquivo `.env` com os dados do seu banco de dados e outras configurações necessárias.

5. Gere a chave da aplicação:

   ```bash
   php artisan key:generate
   ```

6. Execute as migrações e seeders:

   ```bash
   php artisan migrate --seed
   ```

7. Inicie o servidor de desenvolvimento:

   ```bash
   php artisan serve
   ```

## Funcionalidades

### CRUD

- **Localidades:** Cadastro, listagem, atualização e exclusão.
- **Unidades:** Gerenciamento completo das unidades.
- **Funcionários:** Controle total dos dados dos funcionários.

### Sistema de Login com Roles e Permissions

- Gerenciamento de permissões para diferentes usuários.
- Controle seguro de acesso baseado em funções.

### Controle de Ponto com Biometria

- Integração com o leitor biométrico Fingertech Hamster DX USB.
- Registro preciso da presença dos funcionários.

### Relatórios de Presença

- Geração de relatórios detalhados de presença dos funcionários.

### Dias Não Úteis

- Inserção e gerenciamento de feriados e datas não úteis.

## Uso

### Endpoints Principais

- **Localidades:** `/api/localidades`
- **Unidades:** `/api/unidades`
- **Funcionários:** `/api/funcionarios`
- **Autenticação:** `/api/auth/login`
- **Relatórios:** `/api/relatorios/presenca`


## Licença

Este projeto está licenciado sob a [Licença MIT](https://opensource.org/licenses/MIT).

