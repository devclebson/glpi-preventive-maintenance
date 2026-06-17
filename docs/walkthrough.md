# Walkthrough das Mudanças

Todas as alterações propostas no plano de migração foram aplicadas com sucesso. A seguir está o resumo de tudo o que foi realizado:

## Modificações Realizadas

### 1. Limpeza de Metadados
- **Arquivo:** [manifest.json](file:///var/www/glpi/plugins/preventivemaintenance/manifest.json)
- **Alteração:** Os comentários PHP e tags `<?php` foram completamente removidos. O arquivo agora é um arquivo JSON válido e compatível com as regras rígidas do instalador do GLPI 11.

### 2. Configurações de Compatibilidade e Ajuste de Versão
- **Arquivo:** [setup.php](file:///var/www/glpi/plugins/preventivemaintenance/setup.php)
- **Alteração:** A versão máxima suportada foi elevada de `11.0.0` para `12.0.0`, de forma a aceitar instalações em instâncias do GLPI 11.x.

### 3. Ajuste de Classes e Banco de Dados
- **Arquivo:** [profile.class.php](file:///var/www/glpi/plugins/preventivemaintenance/inc/profile.class.php)
- **Alteração:** Removemos a instanciação manual desnecessária de `new DbUtils();` e passamos a usar diretamente a função global helper do GLPI `countElementsInTable()`.
- **Arquivo:** [preventivemaintenance.form.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.form.php)
- **Alteração:** A instanciação manual `$DB = new DB();` foi substituída pelo uso da variável global `global $DB;`, alinhando-se com a arquitetura padrão do GLPI e evitando conexões paralelas errôneas.

### 4. Correção de Consultas Diretas ao Banco (Restrição do GLPI 11)
- **Arquivos:** [setup.php](file:///var/www/glpi/plugins/preventivemaintenance/setup.php) e [preventivemaintenance.form.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.form.php)
- **Alteração:** No GLPI 11, executar consultas SQL diretas usando `$DB->query()` ou `$DB->queryOrDie()` é proibido por motivos de segurança e gera uma exceção. Substituímos todas essas chamadas (na criação de tabelas e no salvamento de formulários) pelo método `$DB->doQuery()`, que é a forma segura e compatível de executar strings SQL cruas no GLPI 11.

### 5. Correção de Assinaturas de Métodos de Permissão (canCreate/canView/etc.)
- **Arquivo:** [preventivemaintenance.class.php](file:///var/www/glpi/plugins/preventivemaintenance/inc/preventivemaintenance.class.php)
- **Alteração:** O GLPI 11 exige que os métodos de permissão herdados de `CommonGLPI` (como `canCreate`, `canView`, `canUpdate` e `canDelete`) declarem explicitamente o tipo de retorno `bool`. Adicionamos a tipagem `public static function ...: bool` para evitar erros de compilação em tempo de execução.

### 6. Correção no Salvamento de Perfis Técnicos (Requisições AJAX)
- **Arquivo:** [preventivemaintenance.form.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.form.php)
- **Alteração:** O salvamento de perfis técnicos selecionados é feito via requisição assíncrona (AJAX). O código antigo chamava `Html::back()` após salvar na sessão. No GLPI 11, o método `Html::back()` gera um redirecionamento HTTP 302 que quebrava o fluxo AJAX, disparando a mensagem "Erro ao salvar a seleção de perfis". Substituímos por um retorno simples `echo "ok"; exit();` quando processado, permitindo que o JavaScript finalize e atualize a página corretamente.

### 7. Geração de Token CSRF Inline nos Formulários
- **Arquivo:** [preventivemaintenance.form.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.form.php)
- **Alteração:** O token CSRF era gerado antes da renderização do cabeçalho (`Html::header()`) e armazenado em uma variável. No GLPI 11, o cabeçalho executa suas próprias chamadas e revalida/altera o estado da sessão de tokens. Gerar o token de forma inline (`Session::getNewCSRFToken()`) diretamente nos campos ocultos dos formulários garante que os tokens submetidos sejam sempre válidos e atualizados.

### 8. Envio de Token CSRF no Cabeçalho HTTP para AJAX
- **Arquivo:** [preventivemaintenance.form.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.form.php)
- **Alteração:** No GLPI 11, para requisições AJAX, a verificação de segurança CSRF é feita especificamente no cabeçalho HTTP `X-Glpi-Csrf-Token` (e não através de parâmetros de corpo POST). Atualizamos a requisição `$.ajax` do jQuery para incluir a propriedade `headers` e passar o token de forma adequada, permitindo a validação de segurança correta na escuta de roteamento do GLPI.

### 9. Substituição de Métodos de Log Inexistentes (Toolbox::logError)
- **Arquivo:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php)
- **Alteração:** No GLPI 11, o método estático `Toolbox::logError()` foi removido. Todas as ocorrências deste método na captura de erros (`try-catch`) do arquivo principal do plugin foram substituídas pelo método padrão `Toolbox::logInFile('php-errors', ... . "\n")`. Isso impede que o PHP lance erros fatais do tipo `undefined method` quando o plugin tenta registrar problemas de banco de dados ou de integração.

### 10. Correção de Estrutura do Campo items_id na Criação de Tickets (GLPI 11)
- **Arquivo:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php)
- **Alteração:** No GLPI 11, o método `handleItemsIdInput()` da classe base `CommonITILObject` processa o campo `items_id` esperando uma estrutura de array associativo no formato `[itemtype => [ids]]` (permitindo associar múltiplos ativos a um único chamado). Passar um número inteiro diretamente causava um aviso/erro do PHP `foreach() argument must be of type array|object, int given`. Alteramos a criação do ticket para passar o computador como `['Computer' => [(int)$computer_id]]`, sanando completamente o alerta e garantindo a correta associação do ativo ao chamado.

### 11. Limpeza de Referências de Autor e Doações
- **Arquivos:** Todos os arquivos PHP, JSON, Markdown e arquivos de licença.
- **Alteração:** Removemos todas as menções diretas ao autor original ("William Oliveira Santos"), à empresa ("WIDA") e ao site de origem ("widatecnologia.com.br") nos cabeçalhos de comentários dos arquivos. O autor foi atualizado para "Generic" e a organização para "GLPI Community" em `manifest.json` e `setup.php`. Adicionalmente, o widget visual de doações (botão com ícone de coração, pop-up de código PIX e QR Code correspondente) foi inteiramente removido do painel principal para garantir neutralidade durante o desenvolvimento.

## Fase 2: Evolução e Novas Funcionalidades

### 12. Migração do Menu para "Ferramentas" (Tools)
- **Arquivos:** [setup.php](file:///var/www/glpi/plugins/preventivemaintenance/setup.php), [inc/menu.class.php](file:///var/www/glpi/plugins/preventivemaintenance/inc/menu.class.php), [front/preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php) e [front/preventivemaintenance.form.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.form.php)
- **Alteração:** Migramos os ganchos do menu do plugin de `plugins` para a aba **"Ferramentas" (Tools)** para melhor integração com outros plugins de gestão (como Frotas). Atualizamos os cabeçalhos HTML `Html::header()` para usar `'tools'` como a aba selecionada.

### 13. Interface Unificada com Layout de Abas (Tabs)
- **Arquivo:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php)
- **Alteração:** O painel do plugin foi reestruturado para exibir uma interface moderna com 4 abas de navegação horizontal:
  1. **Painel (Portal):** Exibe as manutenções cadastradas agrupadas por entidade, com barras de progresso visuais baseadas no tempo decorrido para o vencimento.
  2. **Nova Manutenção:** Integra o formulário completo de cadastro de forma inline (eliminando redirecionamentos extras).
  3. **Chamados Gerados (Histórico):** Lista em tempo real os chamados abertos pelo plugin com seus respectivos status no GLPI.
  4. **Configurações:** Centraliza o interruptor (toggle) de ativação do Auto Ticket e a seleção AJAX dos perfis técnicos que são expostos como responsáveis.

### 14. Integration da Ação Automática (CronTask)
- **Arquivos:** [setup.php](file:///var/www/glpi/plugins/preventivemaintenance/setup.php) e [preventivemaintenance.class.php](file:///var/www/glpi/plugins/preventivemaintenance/inc/preventivemaintenance.class.php)
- **Alteração:** Adicionamos suporte nativo para tarefas automáticas no GLPI:
  - Registramos a tarefa automática `preventivemaintenance` associada ao itemtype `PluginPreventivemaintenancePreventivemaintenance` durante a instalação/desinstalação do plugin.
  - Implementamos os métodos estáticos obrigatórios `cronInfo()` e `cronPreventivemaintenance()` na classe do plugin.
  - Migramos as lógicas de verificação/limpeza de tickets e geração de chamados automáticos (Auto Ticket) de funções locais do frontend para métodos estáticos da classe principal do plugin, permitindo que a tarefa automática rodando em segundo plano (via CLI/externo ou GLPI cron interno) execute exatamente o mesmo processamento.

### 15. Correção de Verificação de Tickets Existentes (GLPI 11)
- **Arquivo:** [preventivemaintenance.class.php](file:///var/www/glpi/plugins/preventivemaintenance/inc/preventivemaintenance.class.php)
- **Alteração:** No GLPI 11, a tabela `glpi_tickets` não possui mais as colunas `items_id` e `itemtype`. Qualquer consulta direta nessas colunas disparava um erro MySQL 1054 ("Unknown column 'items_id' in WHERE"), interrompendo a execução de `hasOpenMaintenanceTicket()` com uma exceção que retornava `false`. Isso fazia o sistema ignorar os chamados criados anteriormente e gerar um chamado novo a cada atualização da página. Corrigimos a consulta para realizar um `INNER JOIN` com a tabela de relações `glpi_items_tickets` no padrão correto do GLPI 11.

### 16. Declaração Global de Variáveis de Banco de Dados no Arquivo do Portal
- **Arquivo:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php)
- **Alteração:** Adicionamos a declaração `global $DB;` no topo do arquivo do portal para garantir a visibilidade da variável `$DB` sob o roteador/controlador do Symfony que encapsula as requisições de arquivos legados no GLPI 11.

---

## Correções Adicionais (Finalização de Manutenção)

### 17. Correção de Erro "Chamado não encontrado no GLPI"
- **Arquivos:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php) e [preventivemaintenance.class.php](file:///var/www/glpi/plugins/preventivemaintenance/inc/preventivemaintenance.class.php)
- **Causa:** O banco de dados continha registros órfãos na tabela customizada `glpi_plugin_preventivemaintenance_tickets` apontando para chamados deletados/expurgados do GLPI. A consulta anterior retornava a primeira correspondência (o registro órfão) em vez do chamado ativo atual.
- **Correção:** 
  1. Alteramos a consulta de ticket ativo para realizar um `INNER JOIN` com `glpi_tickets`, filtrando chamados excluídos ou finalizados (status `Ticket::CLOSED` ou `Ticket::SOLVED`).
  2. Atualizamos o método `cleanResolvedMaintenanceTickets()` para expurgar automaticamente registros órfãos da tabela do plugin sempre que a página for carregada ou a ação automática rodar.

### 18. Correção de Erro "Token de segurança inválido"
- **Arquivo:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php)
- **Causa:** O código realizava uma validação redundante do token CSRF via `Session::validateCSRF($_POST)`. No GLPI 11, o kernel do Symfony intercepta todas as requisições POST através de um listener (`CheckCsrfListener`) e valida o token CSRF previamente. Chamar `Session::validateCSRF` novamente consumia o token ou retornava falso.
- **Correção:** Removemos a verificação manual redundante do token CSRF, delegando a validação de segurança ao kernel nativo do GLPI, que já assegura que qualquer requisição POST recebida no script é legítima.

### 19. Adição das Colunas de Chamado Ativo no Painel
- **Arquivo:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php)
- **Melhoria:** Adicionamos duas novas colunas ("Chamado (ID)" e "Status do Chamado") à tabela principal do Painel (Portal).
- **Comportamento:** Se a manutenção tiver um chamado ativo em aberto, exibe um link para o chamado (ex: `#13548`) e o status atualizado na forma de um badge colorido correspondente ao padrão do GLPI. Caso contrário, exibe `-` (assegurando que chamados antigos/fechados não poluam a visualização).

### 20. Padronização de UX e CSS (Bootstrap 5 e Tabler)
- **Arquivos:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php) e [preventivemaintenance.form.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.form.php)
- **Melhorias Aplicadas:**
  1. **Remoção de CSS Invasivo:** Eliminamos os estilos manuais que alteravam a cor de fundo global (`body`) e forçavam margens e sombras personalizadas, integrando organicamente o plugin ao layout padrão e ao Modo Escuro do GLPI 11.
  2. **Cartões e Abas Bootstrap:** Substituímos as estruturas de abas e containers customizadas pelas classes nativas `card`, `card-tabs`, `nav nav-tabs card-header-tabs` e `card-body` do Bootstrap 5.
  3. **Tabelas e Listas de Entidades:** Cada grupo de entidade agora é renderizado em cartões padrão com tabelas responsivas e elegantes (`table table-hover table-striped align-middle card-table`).
  4. **Badges de Status do Ticket:** Padronizamos todas as exibições de status de chamados utilizando a função helper `getTicketStatusBadge()`, que aproveita as classes *Light* nativas do Tabler (ex: `bg-blue-lt`, `bg-red-lt`, `bg-green-lt`). Isso garante legibilidade, remove problemas de contraste (como texto azul sobre fundo vermelho) e suporta o Modo Escuro.
  5. **Progress Bars e Switches Nativo:** As barras de progresso de status de manutenção e os botões de ligar/desligar do Auto Ticket foram migrados para os componentes nativos progress bar e switches do Bootstrap 5.
  6. **Modal Bootstrap 5:** O modal customizado de finalização de chamados foi substituído pelo padrão `modal fade` do Bootstrap 5, gerenciado nativamente pelo Javascript do Bootstrap (`bootstrap.Modal`).
  7. **Alinhamento do Tamanho das Abas:** Adicionamos o espaçamento inline (`style="padding: 1rem 1.5rem;"`) na aba "Configurações", igualando-o ao das outras três abas ("Painel", "Nova Manutenção", "Chamados Gerados") para manter a consistência visual.
  8. **Botões de Ações Transparentes com Ícones Nativos (Ghost Buttons):** Removemos o fundo sólido dos botões de ação na coluna de ações (tabela principal e aba de chamados gerados). Agora eles utilizam os estilos *ghost* nativos do Tabler (`btn-ghost-success`, `btn-ghost-warning` e `btn-ghost-danger`) juntamente com os ícones modernos (`ti ti-clipboard-check`, `ti ti-edit` e `ti ti-trash`), aplicando a respectiva cor diretamente nos ícones e garantindo um visual muito mais limpo, integrado ao padrão do GLPI 11. O ícone de prancheta com check (`ti-clipboard-check`) representa a ação de preenchimento do procedimento efetuado na conclusão da manutenção.

---

## Suporte a Ativos Recursivos (Entidades Filhas)

### 21. Suporte a Ativos Recursivos
- **Arquivos:** [preventivemaintenance.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.php) e [preventivemaintenance.form.php](file:///var/www/glpi/plugins/preventivemaintenance/front/preventivemaintenance.form.php)
- **Correção:** Anteriormente, quando um computador estava na entidade pai (ex: "Ti") e com a opção de recursividade ativada, ele não era listado ao selecionar uma entidade filha e a validação do backend impedia o salvamento da manutenção.
  1. **JavaScript:** Implementamos a lógica `isAncestorOrSame` utilizando um mapa de parentesco das entidades (`parentEntityMap`) para filtrar dinamicamente computadores recursivos nas entidades filhas no formulário de criação/edição.
  2. **Validação PHP:** Atualizamos o backend em `preventivemaintenance.form.php` para validar corretamente computadores recursivos que pertencem a entidades ancestrais da entidade selecionada (utilizando o helper `getAncestorsOf` do GLPI), permitindo o salvamento com sucesso.

---

## Validação e Próximos Passos
As alterações cobrem tanto a compatibilização básica com o GLPI 11 quanto as melhorias de navegação em abas, migração de menu e processamento automático em segundo plano.

Recomendamos que você:
1. Reinstale e reative o plugin na sua instância de testes do GLPI 11.
2. Acesse o menu **Ferramentas > Preventive Maintenance**.
3. Navegue entre as abas e teste o salvamento de novos agendamentos e a configuração dos perfis técnicos.
4. Verifique em **Administração > Ações Automáticas** se a ação automática `preventivemaintenance` está cadastrada e funcional.
