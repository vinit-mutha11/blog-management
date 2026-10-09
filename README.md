# Simple Blog Management System

A beginner-friendly blog application built with procedural PHP and MySQL. Visitors can read and search posts. Registered users can log in, and administrators can create, edit, and delete posts and manage user roles.

## Features

- Account registration and login with hashed passwords
- User and admin roles
- Post create, read, update, and delete (admins only for changes)
- Search by post title or content
- Sort posts by newest or most liked
- Signed-in users can like and unlike posts
- Admin overview with member, post, and like counts
- Five-post pagination that keeps the current search and sort
- PDO prepared statements, server-side validation, CSRF tokens, and escaped output
- Responsive layout, persistent dark mode, and live post-editor character counts

## Technologies

- PHP 8.0 or newer
- MySQL
- HTML5 and CSS3
- PDO MySQL extension

No framework, package manager, Node.js, or bundled web stack is required.

## Requirements

Install PHP 8+ with the PDO MySQL extension enabled, and have a running MySQL server. You already have MySQL installed; start its server before launching the site.

## MySQL setup

1. Open a MySQL command-line client or another MySQL client and sign in with your MySQL account.
2. Run the SQL script in `database/schema.sql`. For example, from a terminal:

   ```text
   mysql -u root -p < database/schema.sql
   ```

   Enter your MySQL password when prompted. The script creates and selects the `blog` database and creates the `users`, `posts`, and `post_likes` tables.
3. Open `config/database.php` and set `$dbUsername` and `$dbPassword` to your MySQL credentials. The host is `localhost` and the database is `blog`.

If you already have the blog database, run `database/schema.sql` again to create the likes table. The script uses `CREATE TABLE IF NOT EXISTS`, so it preserves existing users and posts.

## Run the project

From the project folder, start PHP's built-in development server:

```text
php -S localhost:8000
```

Open [http://localhost:8000](http://localhost:8000) in your browser. Keep the terminal running while using the site.

## Create the first admin

1. Register a normal account on the website.
2. In MySQL, promote that account (replace the email with the address you registered):

   ```sql
   USE blog;
   UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
   ```

3. Log out and log back in so the admin role is loaded into the session.

There is no preset admin password. The first admin is created through the steps above, and the password is the one chosen during registration.

## Folder structure

```text
blog-management-system/
├── admin/
│   └── users.php
├── assets/
│   ├── app.js
│   └── style.css
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   ├── auth.php
│   ├── footer.php
│   ├── functions.php
│   └── header.php
├── posts/
│   ├── create.php
│   ├── delete.php
│   ├── edit.php
│   ├── index.php
│   ├── like.php
│   └── view.php
├── .gitignore
├── index.php
└── README.md
```

## GitHub upload

1. Create an empty repository on GitHub.
2. From this folder, initialize Git and commit the project:

   ```text
   git init
   git add .
   git commit -m "Create simple blog management system"
   ```

3. Add your GitHub repository as `origin`, then push your branch:

   ```text
   git remote add origin https://github.com/YOUR-USERNAME/YOUR-REPOSITORY.git
   git branch -M main
   git push -u origin main
   ```

Do not upload database credentials. `config/database.php` is local configuration; review its credential values before publishing.

## Notes

- New registrations always receive the `user` role. Promote the first admin using the SQL above.
- This project is intended for local learning and PHP's built-in development server, not direct public production hosting.
- If database errors occur, verify that MySQL is running, `pdo_mysql` is enabled in PHP, and the credentials in `config/database.php` are correct.

## Manual test checklist

After setting up the database and starting the server, check these behaviors in a browser:

1. Register an account, confirm mismatched passwords are rejected, then log in with the new account.
2. Search for text in a post title and in post content; confirm page links keep the search term.
3. As a regular user, confirm posts can be read but the admin pages and post editing pages are blocked.
4. Promote the account to admin using the SQL setup above, then log out and back in.
5. As admin, create a post, edit it, and delete it. Confirm the post list reflects each change.
6. As a signed-in user, like and unlike a post from the list and its detail page; check that the count updates and the Most liked sort changes order.
7. On the users page, confirm the overview counts, change another account's role, and confirm an admin cannot remove their own admin role.
8. Toggle dark mode and reload the page; confirm the preference remains selected. Type in the post editor and confirm the character count updates.
9. Submit an important form without its CSRF token (for example by removing the hidden field in browser developer tools); confirm the request is rejected.
