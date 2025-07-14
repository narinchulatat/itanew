# ITA Backup System

อัตโนมัติสำรองระบบข้อมูลสำหรับ ITA System ที่ไม่กระทบกับโปรเจคเดิม

## คุณสมบัติ

- **สำรองฐานข้อมูล**: ใช้ mysqldump สำรอง MySQL database
- **สำรองไฟล์**: สำรองไฟล์เว็บทั้งหมด
- **บีบอัดอัตโนมัติ**: ใช้ gzip บีบอัดไฟล์
- **ลบไฟล์เก่าอัตโนมัติ**: ลบไฟล์เก่าที่เกิน 7 วันอัตโนมัติ
- **ระบบกู้คืน**: กู้คืนข้อมูลจาก backup
- **หน้าจัดการ**: Web interface สำหรับ admin
- **ระบบ log**: บันทึกการทำงานทั้งหมด
- **รุ่นความปลอดภัย**: ตรวจสอบสิทธิ์ admin

## โครงสร้างไฟล์

```
backup/
├── backup_config.php     # การตั้งค่าระบบ backup
├── backup_manager.php    # ตัวจัดการ backup หลัก
├── auto_backup.php       # สคริปต์สำหรับ cron job
├── cleanup.php           # ลบไฟล์เก่าอัตโนมัติ
├── restore.php           # ระบบกู้คืนข้อมูล
├── index.php             # ป้องกันการเข้าถึงโดยตรง
├── files/                # โฟลเดอร์เก็บไฟล์ backup
│   └── .htaccess        # ป้องกันการเข้าถึงโดยตรง
└── README.md            # ไฟล์นี้

pages/
└── backup_admin.php     # หน้าจัดการ backup ใน admin panel
```

## การติดตั้ง

ระบบนี้ติดตั้งแล้วและพร้อมใช้งาน ไม่ต้องแก้ไขไฟล์เดิม

### ข้อกำหนดระบบ

- PHP 7.0 หรือสูงกว่า
- MySQL/MariaDB
- mysqldump command
- gzip/gunzip commands
- tar command
- สิทธิ์เขียนในโฟลเดอร์ backup/files/

## การใช้งาน

### 1. ผ่าน Web Interface

1. เข้าสู่ระบบด้วยบัญชี admin
2. ไปที่ `pages/backup_admin.php`
3. ใช้ปุ่มต่างๆ สำหรับ:
   - สร้าง backup ฐานข้อมูล
   - สร้าง backup ไฟล์
   - สร้าง backup ทั้งหมด
   - ลบไฟล์เก่า
   - กู้คืนข้อมูล
   - ดาวน์โหลดไฟล์ backup

### 2. ผ่าน Command Line

```bash
# สร้าง backup ฐานข้อมูล
php backup/auto_backup.php database

# สร้าง backup ไฟล์
php backup/auto_backup.php files

# สร้าง backup ทั้งหมด
php backup/auto_backup.php full

# ลบไฟล์เก่า
php backup/cleanup.php

# ดูไฟล์ที่จะถูกลบ (ไม่ลบจริง)
php backup/cleanup.php --dry-run
```

### 3. ตั้งค่า Cron Job (แนะนำ)

เพิ่มใน crontab สำหรับการทำงานอัตโนมัติ:

```bash
# Backup ฐานข้อมูลทุกวันเวลา 02:00
0 2 * * * /usr/bin/php /path/to/backup/auto_backup.php database

# Backup เต็มทุกวันอาทิตย์เวลา 01:00
0 1 * * 0 /usr/bin/php /path/to/backup/auto_backup.php full

# ลบไฟล์เก่าทุกวันเวลา 03:00
0 3 * * * /usr/bin/php /path/to/backup/cleanup.php
```

## การตั้งค่า

แก้ไขไฟล์ `backup_config.php` ตามต้องการ:

```php
// ระยะเวลาเก็บ backup (วัน)
define('BACKUP_RETENTION_DAYS', 7);

// โฟลเดอร์ที่ไม่ต้องการ backup
define('EXCLUDE_DIRS', [
    'backup',
    '.git',
    'node_modules',
    'tmp',
    'temp',
    'cache'
]);
```

## ความปลอดภัย

- ต้องเข้าสู่ระบบด้วยบัญชี admin เท่านั้น
- ไฟล์ backup ป้องกันการเข้าถึงโดยตรง
- บันทึก log การทำงานทั้งหมด
- สร้าง restore point อัตโนมัติก่อนกู้คืน

## การแก้ไขปัญหา

### ปัญหา Permission

```bash
# ให้สิทธิ์เขียนโฟลเดอร์ backup
chmod 755 backup/
chmod 755 backup/files/
```

### ปัญหา MySQL Command

ตรวจสอบให้แน่ใจว่า mysqldump และ mysql command พร้อมใช้งาน:

```bash
which mysqldump
which mysql
```

### ปัญหา Compression

ตรวจสอบให้แน่ใจว่า gzip และ tar command พร้อมใช้งาน:

```bash
which gzip
which tar
```

## Log Files

- `backup/backup.log` - บันทึกการทำงานทั้งหมด
- Log จะ rotate อัตโนมัติเมื่อขนาดเกิน 10MB

## ข้อมูลเพิ่มเติม

- ระบบทำงานอิสระไม่กระทบโปรเจคเดิม
- ไฟล์ backup จะถูกบีบอัดด้วย gzip
- ไฟล์เก่าจะถูกลบอัตโนมัติตามกำหนดเวลา
- ระบบมีการตรวจสอบสถานะและแจ้งเตือนปัญหา

## การพัฒนาต่อ

หากต้องการปรับปรุงเพิ่มเติม:

1. แก้ไขไฟล์ในโฟลเดอร์ `backup/` เท่านั้น
2. อย่าแก้ไขไฟล์หลักของระบบ
3. ทดสอบการทำงานก่อนใช้งานจริง