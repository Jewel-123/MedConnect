# MySQL Won't Start - Troubleshooting Guide

## Current Situation
MySQL Start button in XAMPP is not working, and manual start attempts have failed.

## Step-by-Step Fix

### Step 1: View the Error Log
**Run this file:**
```
C:\xampp\htdocs\medconnect\view_mysql_errors.bat
```

This will show you the **exact error** MySQL is encountering. Common errors:

---

### Common Error 1: Port Already in Use
**Error message:** `Can't start server: Bind on TCP/IP port: Address already in use`

**Fix:**
1. Open Command Prompt as Administrator
2. Run: `netstat -ano | findstr :3306`
3. Note the PID (last column)
4. Run: `taskkill /F /PID [number]`
5. Try starting MySQL again

---

### Common Error 2: Lock File Issue
**Error message:** `InnoDB: Unable to lock ./ibdata1`

**Fix:**
1. Stop all MySQL processes
2. Delete: `C:\xampp\mysql\data\ibdata1.lock`
3. Delete any `.pid` files in `C:\xampp\mysql\data\`
4. Restart computer
5. Try starting MySQL again

---

### Common Error 3: Corrupted System Tables
**Error message:** `Table 'mysql.plugin' doesn't exist`

**Fix:**
This requires MySQL reinstallation or system table restoration. Your data is safe, but system tables need repair.

---

### Common Error 4: Permission Denied
**Error message:** `Can't create/write to file`

**Fix:**
1. Right-click `EMERGENCY_MYSQL_FIX.bat`
2. Select "Run as Administrator"
3. This will fix permissions automatically

---

## Emergency Recovery

If nothing else works, run:
```
C:\xampp\htdocs\medconnect\EMERGENCY_MYSQL_FIX.bat
```

This will:
- Stop all MySQL processes
- Remove lock files
- Fix permissions
- Attempt recovery start

---

## Alternative: Use Different Database Port

If port 3306 is permanently blocked:

1. Edit: `C:\xampp\mysql\bin\my.ini`
2. Find line: `port=3306`
3. Change to: `port=3307`
4. Edit: `C:\xampp\htdocs\medconnect\db.php`
5. Change: `$servername = "localhost";` to `$servername = "localhost:3307";`
6. Start MySQL

---

## Last Resort: Fresh MySQL Start

If all else fails and you need to get working ASAP:

1. **Backup your data:**
   - Copy entire folder: `C:\xampp\mysql\data\medconnect\`
   
2. **Reinstall XAMPP MySQL:**
   - In XAMPP Control Panel, click "Config" next to MySQL
   - Select "Uninstall Service" (if installed as service)
   - Stop XAMPP
   - Rename: `C:\xampp\mysql` to `C:\xampp\mysql_old`
   - Download fresh XAMPP
   - Extract only the `mysql` folder to `C:\xampp\`
   
3. **Restore your data:**
   - Copy backed up `medconnect` folder to `C:\xampp\mysql\data\`
   - Start MySQL

**Your data will be preserved!**

---

## Need More Help?

Run `view_mysql_errors.bat` and share the error message you see. I can provide a specific fix based on the exact error.
