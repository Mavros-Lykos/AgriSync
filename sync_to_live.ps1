Write-Host "Exporting local database to sql/live_sync.sql..." -ForegroundColor Cyan
# Uses local credentials from config
mysqldump -u root -p"projectiles1A#" agrisync > sql/live_sync.sql

if ($?) {
    Write-Host "✅ Database successfully exported!" -ForegroundColor Green
    Write-Host "You can now commit and push to GitHub. Hostinger will automatically load this exact database." -ForegroundColor Yellow
} else {
    Write-Host "❌ Failed to export database. Make sure your local MySQL server is running." -ForegroundColor Red
}
