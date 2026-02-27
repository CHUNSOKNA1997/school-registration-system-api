# Quick Start Testing Guide

## 🚀 Start Testing in 3 Steps

### **Step 1: Start Server**
```bash
cd /home/pc-kira/Documents/RUPP-Y4/Software\ Engineering/school-registration-system-api
php artisan serve
```

### **Step 2: Open Browser**
Visit: **http://localhost:8000**

You should be redirected to `/login`

### **Step 3: Login**
Use your existing credentials or create a test user:

```bash
php artisan tinker
```

Then in tinker:
```php
$user = new App\Models\User();
$user->name = 'Test Admin';
$user->email = 'admin@test.com';
$user->password = bcrypt('password');
$user->is_admin = true;
$user->is_active = true;
$user->save();
exit
```

---

## ✅ Testing Checklist

### **1. Dashboard** ✓
- [ ] Navigate to `/dashboard` after login
- [ ] See statistics cards
- [ ] See student enrollment chart

### **2. Students List** ✓
- [ ] Click "Students" in sidebar
- [ ] See students table (or empty state)
- [ ] Try search functionality
- [ ] Click "Add Student" button

### **3. Create Student** ✓
- [ ] Fill out all required fields:
  - First Name *
  - Last Name *
  - Date of Birth *
  - Gender *
- [ ] Fill optional fields (Khmer name, phone, etc.)
- [ ] Select a class (if available)
- [ ] Click "Create Student"
- [ ] See success toast notification
- [ ] Redirected to students list
- [ ] New student appears in table

### **4. View Student** ✓
- [ ] Click "View" on any student
- [ ] See personal information
- [ ] See contact information
- [ ] See parent/guardian information
- [ ] See academic information sidebar
- [ ] See quick stats
- [ ] See enrollments table (if any)

### **5. Edit Student (Admin Only)** ✓
- [ ] Click "Edit" button
- [ ] Form pre-filled with student data
- [ ] Update some fields
- [ ] Click "Update Student"
- [ ] See success toast
- [ ] Changes reflected in student details

### **6. Delete Student (Admin Only)** ✓
- [ ] Click "Delete" button on a student
- [ ] Confirmation dialog appears
- [ ] Click "Delete" to confirm
- [ ] See success toast
- [ ] Student removed from list

### **7. Manage Enrollments** ✓
- [ ] From student detail, click "Manage Enrollments"
- [ ] See enrollment stats dashboard
- [ ] Click "Add Enrollment"
- [ ] Select subject from dropdown
- [ ] Select teacher from dropdown
- [ ] Click "Add Enrollment"
- [ ] See success toast
- [ ] New enrollment appears in table

### **8. Update Grade** ✓
- [ ] Click "Edit Grade" on an enrollment
- [ ] Enter grade (0-100)
- [ ] Change status if needed
- [ ] Add remarks
- [ ] Click "Update Enrollment"
- [ ] See success toast
- [ ] Grade updated in table (with color coding)

### **9. Remove Enrollment** ✓
- [ ] Click "Remove" on an enrollment
- [ ] Confirmation dialog appears
- [ ] Click "Remove" to confirm
- [ ] See success toast
- [ ] Enrollment removed from table

---

## 🐛 If Something Doesn't Work

### **Issue: Pages don't load / 404 error**
```bash
php artisan route:clear
php artisan config:clear
php artisan view:clear
```

### **Issue: No classrooms/subjects/teachers in dropdowns**
You need to seed data first:
```bash
php artisan db:seed --class=ClassroomSeeder
php artisan db:seed --class=SubjectSeeder
php artisan db:seed --class=TeacherSeeder
```

Or create manually via tinker:
```bash
php artisan tinker
```

```php
// Create a classroom
$class = new App\Models\Classroom();
$class->class_code = 'CLASS-001';
$class->name_en = 'Grade 10A';
$class->name_kh = 'ថ្នាក់ទី១០ក';
$class->grade_level = 10;
$class->capacity = 30;
$class->academic_year = '2025';
$class->save();

// Create a subject
$subject = new App\Models\Subject();
$subject->subject_code = 'MATH-101';
$subject->name_en = 'Mathematics';
$subject->name_kh = 'គណិតវិទ្យា';
$subject->grade_level = 10;
$subject->credits = 3;
$subject->save();

// Create a teacher
$teacher = new App\Models\Teacher();
$teacher->teacher_code = 'TCH-001';
$teacher->first_name = 'John';
$teacher->last_name = 'Smith';
$teacher->date_of_birth = '1985-01-01';
$teacher->gender = 'male';
$teacher->employment_type = 'full_time';
$teacher->hire_date = now();
$teacher->save();
```

### **Issue: "Unauthorized" when trying to edit/delete**
You need admin privileges:
```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'your@email.com')->first();
$user->is_admin = true;
$user->save();
```

### **Issue: Assets not loading**
Rebuild frontend:
```bash
npm run build
```

### **Issue: Inertia version mismatch**
```bash
php artisan inertia:version
npm run build
```

---

## 📸 Expected Results

### **Empty State**
When no students exist, you should see:
- Icon (user group)
- "No students found" message
- "Get started by adding a new student" text
- "Add Student" button

### **Students Table**
When students exist, you should see:
- Student Code column
- Name (English & Khmer)
- Gender
- Class
- Phone
- Status badge (colored)
- Action buttons (View, Edit, Delete)

### **Student Detail Page**
Two-column layout:
- Left: Personal info, Contact info, Parent info, Enrollments
- Right: Academic info, Quick stats, Notes

### **Enrollments Page**
- 4 stat cards (Total, Active, Completed, Avg Grade)
- Enrollments table with subject, teacher, grade, status
- Grade colors: Green (90+), Blue (80-89), Yellow (70-79), Orange (60-69), Red (<60)

---

## ✨ Pro Tips

1. **Use Chrome DevTools** - Check Network tab for API calls
2. **Check Laravel Logs** - `storage/logs/laravel.log`
3. **Use Browser Console** - Check for JavaScript errors
4. **Test with Different Users** - Admin vs Staff to verify permissions
5. **Test Edge Cases** - Empty forms, invalid data, duplicate enrollments

---

## 🎯 Success Criteria

You'll know everything works when:
- ✅ Can navigate between all pages
- ✅ Can create a new student
- ✅ Can view student details
- ✅ Can edit student (as admin)
- ✅ Can delete student (as admin)
- ✅ Can add enrollment
- ✅ Can update grade
- ✅ Can remove enrollment
- ✅ Toast notifications appear
- ✅ Confirmation dialogs work
- ✅ Search works
- ✅ Pagination works (with enough data)
- ✅ No console errors
- ✅ No Laravel errors

---

## 🎉 All Working?

**Congratulations!** Your Student Management Module is fully functional!

**Next steps:**
1. Add more students to test with larger datasets
2. Test performance with pagination
3. Start Phase 2 (Payments or Teachers module)
4. Show off your working demo! 🚀

**Need help?** Check the logs:
- Laravel: `storage/logs/laravel.log`
- Browser: F12 → Console tab
- Network: F12 → Network tab
