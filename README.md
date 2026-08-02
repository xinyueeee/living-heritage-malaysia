# 🇲🇾 Living Heritage Malaysia

A web-based platform designed to promote, preserve, and encourage participation in Malaysia's living heritage through cultural experiences, festivals, community engagement, and reward-based activities.

---

## 📖 Project Overview

Living Heritage Malaysia is a Final Year Project (FYP) developed using Laravel. The platform allows users to discover Malaysian cultural experiences, browse upcoming festivals, participate in communities, and engage with heritage-related activities.

The system aims to provide a centralized platform for promoting Malaysia's cultural heritage while encouraging public participation and cultural preservation.

---

## ✨ Features

### 🏠 Home
- Hero section with search
- Featured Cultural Experiences
- Upcoming Festivals
- Community Highlights
- Passport & Rewards Preview
- Responsive design

### 🔍 Discovery
- Search cultural experiences
- Search by location
- Filter by category
- Filter by experience type
- Sort by newest / oldest
- Pagination

### 👤 User
- Google Login (Supabase Authentication)
- Profile Management

### 🎉 Festival
- Browse upcoming festivals
- Festival details

### 👥 Community *(In Progress)*
- Community listing
- Join community
- Community discussions

### 🎁 Engagement & Rewards *(In Progress)*
- Passport
- Badges
- Rewards

---

# 🛠 Tech Stack

| Technology | Usage |
|------------|-------|
| Laravel 12 | Backend Framework |
| PHP 8.2 | Backend Language |
| PostgreSQL (Supabase) | Database |
| Blade | Frontend Template |
| Tailwind CSS | Styling |
| Vite | Asset Bundler |
| Git & GitHub | Version Control |
| Supabase Auth | Google Authentication |

---

# 📂 Project Structure

```
app/
database/
public/
resources/
routes/
storage/
```

---

# ⚙️ Installation

## 1. Clone Repository

```bash
git clone https://github.com/your-username/living-heritage-malaysia.git
```

## 2. Enter Project

```bash
cd living-heritage-malaysia
```

## 3. Install PHP Dependencies

```bash
composer install
```

## 4. Install Node Packages

```bash
npm install
```

## 5. Configure Environment

Copy the environment file.

```bash
cp .env.example .env
```

Fill in your database credentials.

Example (Supabase PostgreSQL):

```env
DB_CONNECTION=pgsql
DB_HOST=
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Also configure:

```env
VITE_SUPABASE_URL=
VITE_SUPABASE_ANON_KEY=
```

> **Note:** The `.env` file is **not** committed to GitHub. Each team member must configure their own environment variables.

---

## 6. Generate Application Key

```bash
php artisan key:generate
```

---

## 7. Run Database Migrations

```bash
php artisan migrate
```

---

## 8. Start Development Server

Laravel

```bash
php artisan serve
```

Vite

```bash
npm run dev
```

Open:

```
http://127.0.0.1:8000
```

---

# 🖼 Experience Images

Experience images are loaded from:

```
public/images/experiences/
```

Store the relative path inside the database.

Example:

```
images/experiences/batik-workshop.jpg
```

---

# 👨‍💻 Team Workflow

This project uses **Git Feature Branch Workflow**.

## Create Feature Branch

```bash
git checkout -b feature/your-feature
```

Example:

```
feature/home-page
feature/profile-management
feature/discover
```

## Commit Changes

```bash
git add .
git commit -m "Describe your changes"
```

## Push Branch

```bash
git push origin feature/your-feature
```

## Merge into Main

After completing the feature:

1. Create a Pull Request
2. Review changes
3. Resolve conflicts (if any)
4. Merge into `main`

Other members update using:

```bash
git checkout main
git pull origin main
```

---

# 🗄 Database

Database is hosted on **Supabase PostgreSQL**.

Schema changes should always be performed through Laravel migrations.

Example:

```bash
php artisan make:migration add_column_to_table
```

After pushing the migration:

```bash
git pull
php artisan migrate
```

Do **not** edit the production database schema manually without creating a corresponding migration.

---

# 📄 License

This project is developed for academic purposes as part of the Bachelor of Software Engineering (Honours) Final Year Project.
