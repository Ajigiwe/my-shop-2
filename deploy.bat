@echo off
echo ==============================================
echo        ASO Online Market - Auto Deploy
echo ==============================================
echo.

:: CONFIGURATION
:: Change these if your domain name or security token changes
set LIVE_URL=https://asoonlinemarket.com
set DEPLOY_SECRET=asodeploy123

:: Stage all changes
echo Staging changes...
git add .

:: Ask user for commit message
set /p commit_msg="Enter commit message (or press Enter for 'Auto-deploy update'): "
if "%commit_msg%"=="" set commit_msg=Auto-deploy update

:: Commit changes
echo.
echo Committing changes...
git commit -m "%commit_msg%"

:: Push to GitHub
echo.
echo Pushing changes to GitHub (branch: main)...
git push origin main

if %ERRORLEVEL% neq 0 (
    echo.
    echo [ERROR] Push to GitHub failed! Please check your internet or git credentials.
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ==============================================
echo SUCCESS: Code pushed to GitHub!
echo Triggering live server deployment...
echo ==============================================
echo.

curl -k "%LIVE_URL%/deploy.php?token=%DEPLOY_SECRET%"

echo.
echo.
echo ==============================================
echo Sync attempt finished. Check output above.
echo ==============================================
echo.
pause

