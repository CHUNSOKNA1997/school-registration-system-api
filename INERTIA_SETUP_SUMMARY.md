# Inertia.js + React + Shadcn UI Setup Summary

## ✅ Completed Setup

### 1. Backend (Laravel + Inertia)
- ✅ Installed `inertiajs/inertia-laravel` package
- ✅ Created `HandleInertiaRequests` middleware with shared auth user and flash messages
- ✅ Registered Inertia middleware in `bootstrap/app.php`
- ✅ Created root blade template at `resources/views/app.blade.php`
- ✅ Created Web DashboardController at `app/Http/Controllers/Web/DashboardController.php`
- ✅ Added dashboard route to `routes/web.php`

### 2. Frontend (React + Vite)
- ✅ Installed React 19.2.0 and React DOM
- ✅ Installed Inertia React adapter `@inertiajs/react`
- ✅ Installed Vite React plugin `@vitejs/plugin-react`
- ✅ Configured `vite.config.js` for React + Inertia
- ✅ Created React app entry point at `resources/js/app.jsx`

### 3. Styling (Tailwind CSS v4)
- ✅ Tailwind CSS v4 already installed
- ✅ Updated `resources/css/app.css` to include JSX/TSX files
- ✅ Changed font to 'Inter' for better UI

### 4. Shadcn UI Dependencies
- ✅ Installed `class-variance-authority`
- ✅ Installed `clsx`
- ✅ Installed `tailwind-merge`
- ✅ Installed `lucide-react` (icon library)
- ✅ Created utility function at `resources/js/lib/utils.js`

### 5. Project Structure
Created the following folder structure:
```
resources/js/
├── Components/          # Reusable UI components (Shadcn components will go here)
├── Layouts/
│   └── AuthenticatedLayout.jsx  # Main layout with navigation
├── Pages/
│   └── Dashboard.jsx    # Dashboard page
├── lib/
│   └── utils.js         # Utility functions (cn helper)
└── app.jsx              # React app entry point
```

### 6. Configuration Files
- ✅ Created `jsconfig.json` for path aliases (`@/*` → `resources/js/*`)
- ✅ Updated `vite.config.js` with React plugin and aliases

## 📁 Key Files Created

### Backend
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Controllers/Web/DashboardController.php`
- `resources/views/app.blade.php`

### Frontend
- `resources/js/app.jsx`
- `resources/js/Layouts/AuthenticatedLayout.jsx`
- `resources/js/Pages/Dashboard.jsx`
- `resources/js/lib/utils.js`
- `jsconfig.json`

### Configuration
- `vite.config.js` (updated)
- `routes/web.php` (updated)
- `bootstrap/app.php` (updated)
- `resources/css/app.css` (updated)

## 🚀 Next Steps

### To Run the Development Server:
```bash
# Terminal 1 - Start Laravel server
php artisan serve

# Terminal 2 - Start Vite dev server
npm run dev
```

### To Add Shadcn Components:
Shadcn UI doesn't have an official CLI for React without TypeScript, but you can:
1. Manually copy components from https://ui.shadcn.com/docs/components
2. Adapt them to JSX (remove TypeScript types)
3. Place them in `resources/js/Components/ui/`

Example components to add first:
- Button
- Card
- Input
- Label
- Table
- Dialog
- Dropdown Menu
- Badge

### To Build for Production:
```bash
npm run build
```

## 🔒 Authentication Notes

The current setup uses Sanctum for authentication. You'll need to:
1. Create login/register pages using Inertia
2. Update `AuthController` to return Inertia responses for web routes
3. Add proper session-based authentication for web routes

## 📊 Current Routes

### Web Routes (Inertia)
- `GET /` → Redirects to dashboard
- `GET /dashboard` → Dashboard page (requires auth)

### API Routes (JSON)
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`
- `GET /api/v1/dashboard` (API endpoint)
- All other API endpoints...

## ⚠️ Important Notes

1. **Authentication**: Currently using `auth:sanctum` middleware. You may need to adjust this for session-based auth in web routes.

2. **CSRF Protection**: Inertia automatically handles CSRF tokens. Make sure session configuration is correct.

3. **Asset Building**: Run `npm run dev` during development and `npm run build` for production.

4. **Path Aliases**: Use `@/` to import from `resources/js/`:
   ```jsx
   import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
   ```

5. **Shared Data**: Auth user and flash messages are automatically shared to all Inertia pages via `HandleInertiaRequests` middleware.

## 🎨 Shadcn UI Component Example

To add a Button component, create `resources/js/Components/ui/button.jsx`:
```jsx
import { cn } from "@/lib/utils"

export function Button({ className, variant = "default", size = "default", ...props }) {
  return (
    <button
      className={cn(
        "inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2",
        "disabled:pointer-events-none disabled:opacity-50",
        {
          "bg-primary text-primary-foreground hover:bg-primary/90": variant === "default",
          "bg-destructive text-destructive-foreground hover:bg-destructive/90": variant === "destructive",
          "border border-input bg-background hover:bg-accent hover:text-accent-foreground": variant === "outline",
        },
        {
          "h-10 px-4 py-2": size === "default",
          "h-9 rounded-md px-3": size === "sm",
          "h-11 rounded-md px-8": size === "lg",
        },
        className
      )}
      {...props}
    />
  )
}
```

## 📦 Installed Packages

### Dependencies
- `@inertiajs/react: ^2.2.18`
- `@vitejs/plugin-react: ^5.1.1`
- `react: ^19.2.0`
- `react-dom: ^19.2.0`
- `class-variance-authority: latest`
- `clsx: latest`
- `tailwind-merge: latest`
- `lucide-react: latest`

### Already Installed
- `@tailwindcss/vite: ^4.0.0`
- `tailwindcss: ^4.0.0`
- `vite: ^7.0.4`
- `laravel-vite-plugin: ^2.0.0`

---

**Setup completed successfully!** 🎉

You can now start building your admin dashboard with React, Inertia.js, and Shadcn UI components.
