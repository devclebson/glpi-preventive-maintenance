# Plugin de Manutenção Preventiva para GLPI

Este plugin foi desenvolvido para agendar, monitorar e automatizar manutenções preventivas de computadores no GLPI.

---

## 📌 Visão Geral

O plugin permite criar planos de manutenção recorrentes para computadores e automatiza a geração de chamados de suporte técnico, ajudando a garantir que os equipamentos recebam as manutenções preventivas programadas nos prazos corretos.

### Funcionalidades Principais:
*   ✔ **Interface Unificada em Abas (Tabs):** Painel, Nova Manutenção, Chamados Gerados e Configurações centralizados em um único local operacional.
*   ✔ **Visual Moderno e Modo Escuro:** Interface 100% migrada para Bootstrap 5 e componentes Tabler nativos do GLPI 11.
*   ✔ **Alertas Visuais e Barra de Progresso:** Status visual baseado no tempo restante para o vencimento (✅ Em dia / ⚠️ Atenção / ❌ Urgente).
*   ✔ **Ação Automática (CronTask):** Geração automática de chamados integrada nativamente às ações agendadas do GLPI.
*   ✔ **Fluxo de Conclusão Prático:** Finalize a manutenção e feche o chamado associado informando o procedimento executado diretamente da tabela.
*   ✔ **Suporte a Ativos Recursivos:** Suporta computadores herdados de entidades ancestrais/pai com a propriedade recursiva habilitada.

---

## 🚀 Instalação e Configuração

### Requisitos:
*   **GLPI:** Versão 9.x, 10.x ou 11.x (Homologado e compatibilizado para GLPI 11)
*   **PHP:** Versão 7.4 ou superior (Compatível com PHP 8.x)

### Passos para Instalação:
1.  Extraia o diretório na pasta de plugins do seu GLPI:  
    `/var/www/glpi/plugins/preventivemaintenance`
2.  Acesse o GLPI no menu **Configurações > Plugins** (ou **Setup > Plugins**).
3.  Instale e ative o plugin **Manutenção Preventiva**.
4.  O plugin passará a estar disponível no menu principal em:  
    **Ferramentas > Manutenção Preventiva** (ou **Tools > Preventive Maintenance**).
5.  Configure o perfil dos técnicos em **Configurações > Perfis** para liberar os acessos de escrita, leitura e exclusão do plugin.

### Ação Automática:
Para que a verificação de prazos e a geração de chamados ocorra em segundo plano de forma contínua:
1.  Acesse **Administração > Ações Automáticas** (ou **Administration > Automatic Actions**).
2.  Localize a ação `preventivemaintenance`.
3.  Defina o modo de execução como **CLI** (recomendado via cron do servidor) ou **GLPI** (via tráfego de usuários).

---

## 📂 Documentação do Projeto

Para informações detalhadas sobre a migração de compatibilidade efetuada para o GLPI 11 e o relatório de melhorias implementadas, consulte a pasta [docs/](file:///var/www/glpi/plugins/preventivemaintenance/docs/):

*   [Relatório de Melhorias](file:///var/www/glpi/plugins/preventivemaintenance/docs/relatorio_de_melhorias.md): Resumo de todas as melhorias visuais, de segurança e de recursos aplicados.
*   [Walkthrough Técnico](file:///var/www/glpi/plugins/preventivemaintenance/docs/walkthrough.md): Documento contendo as 21 modificações técnicas realizadas no código-fonte do plugin para compatibilidade e evolução.

---

## 📜 Licença

Licenciado sob GNU GPLv2+ - Ver arquivo LICENSE para a licença completa.

*Desenvolvido por:*  
**GLPI Community**

*🔧 Manutenção preventiva = Menos falhas + Mais produtividade!*
