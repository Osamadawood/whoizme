set -e

# كله داخل مجلد الـ SCSS
cd public/assets/scss

# مثال لكتلة واحدة سليمة — غيّر الاسم والمحتوى حسب الملف اللي عايز تعمله
cat > components/_example.scss <<'SCSS'
/* مثال: ملف Component جديد */
@mixin apply() {
  .example { padding: 1rem; }
}
SCSS

