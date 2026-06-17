# Relatório de Evolução e Compatibilização do Plugin

Este documento resume as melhorias e correções efetuadas no plugin **Manutenção Preventiva** (`preventivemaintenance`) para torná-lo 100% compatível com as regras rígidas do **GLPI 11**, além de aplicar melhorias de usabilidade (UX/UI) e suporte hierárquico a ativos.

---

## 🛠️ 1. Compatibilização com o GLPI 11

Resolvemos os seguintes problemas críticos de infraestrutura que causavam falhas fatais ou comportamentos inválidos no GLPI 11:

*   **Validação do `manifest.json`:** Removemos comentários PHP e tags que violavam o validador rigoroso do GLPI 11. O arquivo foi transformado em um JSON válido.
*   **Correção de Queries Diretas (Segurança):** Substituímos o método `$DB->query()` (bloqueado no GLPI 11 por motivos de segurança) pelo método seguro `$DB->doQuery()` em rotinas de criação de tabelas e processamentos de formulário.
*   **Assinatura de Métodos de Permissão:** Adicionamos a declaração explícita de tipo de retorno `bool` nos métodos herdados de `CommonGLPI` (ex: `public static function canCreate(): bool`), evitando erros fatais em tempo de execução.
*   **Associação de Ativos ao Chamado:** Ajustamos o envio do campo `items_id` na criação de tickets automatizados. O GLPI 11 exige uma estrutura de array associativo `['Computer' => [(int)$id]]` no lugar de um inteiro simples, resolvendo os avisos PHP.
*   **Substituição de Métodos de Log Depreciados:** Substituímos o método inexistente `Toolbox::logError` pela função padrão do GLPI 11 `Toolbox::logInFile('php-errors', ...)`.
*   **Correção de Instanciação do Banco de Dados:** Substituímos a declaração incorreta `new DbUtils()` por `countElementsInTable()` e a instanciação paralela `$DB = new DB()` por `global $DB`, garantindo alinhamento arquitetural.

---

## 🎨 2. Evolução de Interface e Experiência do Usuário (UX/UI)

Redesenhamos a experiência visual e fluxo operacional do plugin:

*   **Migração do Menu Principal:** Movemos o gancho do menu de *Plugins* para a seção **Ferramentas (Tools)**, padronizando o acesso junto com outros plugins de gestão (como Frotas).
*   **Interface Unificada em Abas (Tabs):** O fluxo foi centralizado em uma única tela contendo 4 abas horizontais modernas (sem redirecionamentos desnecessários):
    1.  **Painel (Portal):** Listagem de manutenções e barra de progresso visual de vencimento.
    2.  **Nova Manutenção:** Formulário de cadastro inline.
    3.  **Chamados Gerados:** Histórico e status dos chamados abertos.
    4.  **Configurações:** Ativação do Auto Ticket e gestão de Perfis Técnicos autorizados.
*   **Padronização Bootstrap 5 & Tabler:** Adequamos toda a folha de estilo eliminando estilos customizados invasivos que quebravam o Modo Escuro. Adotamos o padrão Tabler nativo do GLPI 11 para cartões, tabelas, progress bars e modais.
*   **Badges de Status:** Unificamos e padronizamos as cores e contraste das etiquetas de status de chamados utilizando a função nativa `getTicketStatusBadge()`.
*   **Botões de Ação Transparentes (Ghost Buttons):** Atualizamos a coluna *Actions* com botões de estilo *ghost* (fundo transparente, cor sutil no hover) e ícones modernos do Tabler (`ti-clipboard-check`, `ti-edit` e `ti-trash`).
*   **Alinhamento de Layout:** Corrigimos o espaçamento e padding da aba *Configurações* para alinhar perfeitamente com o cabeçalho das outras abas.

---

## ⚙️ 3. Correções Funcionais e de Fluxo

Ajustamos regras de negócio e integrações automáticas:

*   **Geração de Chamados Duplicados:** Corrigimos a query de verificação de chamado ativo que procurava colunas inexistentes na tabela de chamados (`glpi_tickets`), o que gerava um chamado novo a cada atualização da página.
*   **Segurança CSRF e AJAX:** Corrigimos falhas de "Token de segurança inválido" nas ações do painel enviando o token CSRF de forma inline no formulário e no cabeçalho HTTP `X-Glpi-Csrf-Token` nas requisições assíncronas (AJAX), delegando a verificação ao kernel do Symfony.
*   **Limpeza de Registros Órfãos:** Implementamos uma rotina automática para expurgar referências a chamados deletados do banco de dados, corrigindo o erro "Chamado não encontrado".
*   **Suporte a Ativos Recursivos:** Permitimos que computadores criados em entidades pai com opção de recursividade ativa (`is_recursive = 1`) sejam listados e validados com sucesso ao cadastrar manutenções em entidades filhas.
*   **Integração do Agendador de Tarefas (CronTask):** Registramos o plugin nas tarefas automáticas do GLPI, permitindo que a varredura e abertura de chamados (Auto Ticket) ocorra em segundo plano de forma nativa.

---

## 📊 4. Visão Geral das Ações da Tabela de Manutenções

A nova coluna de ações apresenta os botões com o seguinte comportamento:

| Ação | Ícone | Cor (Ghost) | Descrição |
| :--- | :---: | :---: | :--- |
| **Finalizar Manutenção** | `ti ti-clipboard-check` | Verde | Abre o modal para preencher o procedimento e fechar o chamado correspondente no GLPI. |
| **Editar Registro** | `ti ti-edit` | Laranja/Amarelo | Abre o formulário para editar datas e responsáveis da manutenção. |
| **Excluir Registro** | `ti ti-trash` | Vermelho | Remove o agendamento da manutenção preventiva do sistema. |

---

## 🔒 5. Neutralidade e Limpeza de Marca
Removemos todas as referências ao autor original, logos externas, botões de doação visualmente invasivos e pop-up PIX para garantir neutralidade corporativa no uso da ferramenta.
