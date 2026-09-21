⚡ Cyber 1v1 Gaming Hub (PHP & MySQL)
A lightweight, secure, and real-time 1v1 multiplayer gaming platform built using PHP (PDO), MySQL, and modern CSS (Glassmorphism UI). Players can create private rooms using unique 4-digit codes, place bets from their wallet balance, and compete in multiple game modes.

🚀 Key Features
🔐 Secure Authentication & Wallet System: User login, secure session management, wallet balance tracking, and transaction logs.

🎲 Multiple Game Modes:

2-Player Ludo Battle: Classic board game mechanics.

2-Player Blitz Chess: Fast-paced chess room setup.

1v1 Custom Q&A Challenge: Create custom questions and answers to challenge friends directly.

🔑 4-Digit Room Code Matchmaking: Instant room creation with a unique random 4-digit numeric code for seamless friend matchmaking.

💰 Automated Commission & Payouts: Built-in calculation logic for winner payouts (75%) and platform fees (25%).

🎨 Cyber-Themed Glassmorphism UI: Responsive modern design featuring dark mode aesthetics, gradients, and a clean user dashboard.

🛠️ Tech Stack
Backend: PHP (PDO for secure database queries)

Database: MySQL / MariaDB

Frontend: HTML5, CSS3, JavaScript (Responsive Grid Layout)

⚙️ Installation & Setup
Clone the Repository:

Bash
git clone https://github.com/your-username/cyber-1v1-gaming-hub.git
Move to Server Directory: Place the project folder in your htdocs (XAMPP) or www (WAMP/LAMP) root directory.

Database Configuration:

Create a MySQL database (e.g., gaming_hub).

Import your database structure (users, matches, transactions tables).

Update your database credentials in config.php.

Run the Project: Open your browser and navigate to:

Plaintext
http://localhost/cyber-1v1-gaming-hub/
📂 Code Structure Snapshot (index.php)
Handles secure room creation with balance validation.

Generates safe 4-digit unique room codes using database collision checks.

Manages transaction history logs for entry fees and wallet deductions.

Provides dynamic UI toggles for custom Q&A game modes.
