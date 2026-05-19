@echo off
echo ==============================================
echo        ASO Online Market - Auto Deploy
echo ==============================================
echo.

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
echo GitHub Actions is now syncing changes to your host.
echo ==============================================
echo.
pause
