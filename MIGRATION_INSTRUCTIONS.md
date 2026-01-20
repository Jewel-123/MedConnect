# Database Migration Instructions

## How to Run the Migration

You have **TWO OPTIONS** to execute the database migration:

### Option 1: Browser-Based Migration (Recommended)

1. Open your browser
2. Navigate to: `http://localhost/MedConnect/run_migration.html`
3. Click the "Execute Migration" button
4. Wait for completion (you'll see a detailed log and success/error summary)

### Option 2: phpMyAdmin (Manual)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select the `medconnect` database
3. Go to the "SQL" tab
4. Open the file `c:\xampp\htdocs\MedConnect\core_system_schema.sql` in a text editor
5. Copy ALL the SQL content
6. Paste it into the SQL query box in phpMyAdmin
7. Click "Go" to execute

## What the Migration Does

- ✅ Creates **18 new tables** for all 8 core modules
- ✅ Enhances existing tables with new columns
- ✅ Preserves ALL existing data (100% safe)
- ✅ Sets up indexes and foreign key relationships
- ✅ Seeds initial configuration data

## Expected Results

After successful migration, you should have these NEW tables:
- symptom_attachments
- appointments
- doctor_queue
- consultation_messages
- consultation_attachments
- pharmacy_profiles
- pharmacy_inventory
- prescription_orders
- delivery_tracking
- payment_transactions
- revenue_splits
- payouts
- pharmacy_earnings
- notification_preferences
- scheduled_notifications
- notification_templates
- access_logs
- compliance_events

## Verification

To verify the migration was successful:

```sql
-- Check table count
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'medconnect';

-- Should see the new tables
SHOW TABLES LIKE 'symptom_attachments';
SHOW TABLES LIKE 'payment_transactions';
SHOW TABLES LIKE 'pharmacy_profiles';
```

## Troubleshooting

If you encounter errors:
1. Make sure XAMPP MySQL is running
2. Ensure you have the correct database credentials in db.php
3. Check that the `medconnect` database exists
4. Verify you have CREATE and ALTER table permissions

Once you've run the migration, continue with the next modules!
