# 🎮 Quiz Multiplayer

> Jogo de perguntas e respostas em tempo real com QR Code, sistema de níveis e gerador de perguntas com Inteligência Artificial.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)

---

## 📋 Sobre o Projeto

Projeto desenvolvido para a disciplina de **Linguagens Web 2**, com o objetivo de criar uma aplicação web interativa e dinâmica utilizando PHP, MySQL e XAMPP.

O Quiz Multiplayer permite que vários jogadores participem simultaneamente de uma partida de perguntas e respostas através dos seus celulares, acessando via QR Code. O sistema conta com gamificação completa (XP, níveis e ranking) e um painel administrativo com gerador de perguntas por Inteligência Artificial.

---

## ✨ Funcionalidades

- 📱 **Acesso via QR Code** — jogadores entram escaneando o código com o celular
- 👥 **Multiplayer em tempo real** — vários jogadores simultâneos na mesma sala
- ⏱️ **Timer automático** — 30 segundos por pergunta com avanço automático
- 🏆 **Sistema de pontuação** — pontos baseados na velocidade de resposta
- ⭐ **Sistema de XP e Níveis** — Iniciante → Aprendiz → Guerreiro → Expert → Mestre
- 📊 **Ranking ao vivo** — placar atualizado em tempo real durante o jogo
- 🎊 **Tela de resultado** — pódio animado com confetes coloridos
- 🤖 **Gerador de perguntas com IA** — cria perguntas automaticamente sobre qualquer tema
- ✏️ **Painel administrativo** — cadastra, visualiza e deleta perguntas
- 🎨 **Visual Dark Neon** — interface moderna e responsiva para mobile

---

## 🛠️ Tecnologias Utilizadas

| Tecnologia | Uso |
|---|---|
| PHP 8 | Backend e APIs REST |
| MySQL / MariaDB | Banco de dados |
| XAMPP | Ambiente de desenvolvimento local |
| JavaScript (Vanilla) | Interatividade e polling em tempo real |
| CSS3 | Estilização dark neon com animações |
| Groq API | Geração de perguntas com IA (gratuito) |
| QR Server API | Geração de QR Code dinâmico |

---

## 📁 Estrutura do Projeto

```
quiz/
├── index.php           # Tela inicial do host com QR Code
├── cadastro.php        # Cadastro do jogador via celular
├── host.php            # Tela do host durante o jogo
├── jogador.php         # Tela do jogador durante o jogo
├── ranking.php         # Resultado final com pódio
│
├── api/
│   ├── status.php      # Retorna status da sala em tempo real
│   ├── pergunta.php    # Retorna a pergunta atual
│   ├── responder.php   # Salva resposta e calcula pontos
│   └── avancar.php     # Avança para a próxima pergunta
│
├── admin/
│   ├── index.php       # Painel de gerenciamento de perguntas
│   ├── gerar_ia.php    # API de geração de perguntas com IA
│   └── salvar.php      # Salva perguntas no banco
│
├── db/
│   └── conexao.php     # Conexão com o banco de dados
│
├── assets/
│   └── style.css       # Estilos dark neon
│
└── banco.sql           # Script SQL para criar o banco
```

---

## 🚀 Como Rodar o Projeto

### Pré-requisitos
- XAMPP instalado (Apache + MySQL)
- MySQL Workbench (opcional)
- Navegador moderno
- Celular na mesma rede Wi-Fi

### Passo 1 — Clone o repositório
```bash
git clone https://github.com/seu-usuario/quiz.git
```

### Passo 2 — Copie para o htdocs
```
C:\xampp\htdocs\quiz\
```

### Passo 3 — Crie o banco de dados
1. Abra o XAMPP e inicie o **Apache** e o **MySQL**
2. Abra o **MySQL Workbench** ou **phpMyAdmin**
3. Execute o arquivo `banco.sql`

### Passo 4 — Configure a conexão
Abra `db/conexao.php` e verifique as configurações:
```php
$host    = "localhost";
$usuario = "root";
$senha   = "";           // padrão XAMPP
$banco   = "quiz_game";
```

### Passo 5 — Configure a IA (opcional)
Abra `admin/gerar_ia.php` e adicione sua chave do Groq:
```php
$chave = 'gsk_sua_chave_aqui';
```
> Crie uma chave gratuita em: https://console.groq.com

### Passo 6 — Descubra seu IP local
```
Windows: ipconfig → Endereço IPv4
```
Abra `index.php` e atualize:
```php
$ip = 'SEU_IP_AQUI'; // ex: 192.168.1.16
```

### Passo 7 — Acesse o jogo
```
http://localhost/quiz
```

---

## 🎮 Como Jogar

1. **Host** abre `http://localhost/quiz` no computador/projetor
2. Um **QR Code** é gerado automaticamente
3. **Jogadores** escaneiam o QR Code com o celular
4. Cada jogador preenche **nome, e-mail e data de nascimento**
5. **Host** clica em "Iniciar Jogo"
6. As perguntas aparecem nos celulares com **30 segundos** para responder
7. O timer avança automaticamente para a próxima pergunta
8. Ao final, o **ranking com pódio** é exibido para todos

---

## ⚙️ Painel Administrativo

Acesse em: `http://localhost/quiz/admin`

- **Cadastrar perguntas** manualmente com 4 opções e resposta correta
- **Gerar perguntas com IA** — digite o tema e a quantidade
- **Visualizar** todas as perguntas cadastradas
- **Deletar** perguntas indesejadas

---

## 📊 Sistema de Pontuação

| Situação | Pontos | XP |
|---|---|---|
| Acerto em menos de 5s | ~870 pts | 50 XP |
| Acerto em 15s | ~600 pts | 50 XP |
| Acerto em 29s | ~200 pts | 50 XP |
| Erro | 0 pts | 10 XP |

### Níveis
| Nível | Nome | XP necessário |
|---|---|---|
| 1 | ⭐ Iniciante | 0 XP |
| 2 | 🔥 Aprendiz | 100 XP |
| 3 | ⚡ Guerreiro | 300 XP |
| 4 | 💎 Expert | 700 XP |
| 5 | 👑 Mestre | 1500 XP |

---

## 👩‍💻 Autora

Desenvolvido com 💜 por **Vanessa Sousa**

Disciplina: Linguagens Web 2

---

## 📄 Licença

Este projeto foi desenvolvido para fins acadêmicos.