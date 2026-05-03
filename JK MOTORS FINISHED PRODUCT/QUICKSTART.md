# Quick Start Guide - JK Motorparts System

## 🚀 Quick Setup (5 Minutes)

### 1. Prerequisites Check
- ✅ XAMPP installed and running
- ✅ Apache and MySQL services started
- ✅ Project folder in `htdocs` directory

### 2. Database Setup
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create database: `jk_motorparts`
3. Import: `database/schema.sql`
4. Done! ✅

### 3. Access System
1. Open browser: http://localhost/jk-motorparts
2. Login with:
   - **Admin**: admin@jkmotorparts.com / admin123
   - **Technician**: technician@jkmotorparts.com / admin123

## 📋 Default Accounts

### Admin
- **Email**: admin@jkmotorparts.com
- **Password**: admin123
- **Access**: Full system access

### Technician
- **Email**: technician@jkmotorparts.com
- **Password**: admin123
- **Access**: RSA ticket management

### Customer
- **Action**: Register new account
- **Access**: Purchase products, request RSA, redeem rewards

## 🎯 First Steps

### As Admin:
1. ✅ Login to admin account
2. ✅ View dashboard with statistics
3. ✅ Add products in POS → Products
4. ✅ Process a test sale in POS
5. ✅ View reports and analytics
6. ✅ Manage RSA requests
7. ✅ Configure rewards catalog

### As Customer:
1. ✅ Register new account
2. ✅ Request RSA assistance
3. ✅ View reward points
4. ✅ Redeem rewards
5. ✅ View transaction history

### As Technician:
1. ✅ Login to technician account
2. ✅ View assigned tickets
3. ✅ Update ticket status
4. ✅ Add technician notes

## 🔧 Key Features to Test

### POS System
- [ ] Add product to inventory
- [ ] Process sale with cart
- [ ] Generate receipt
- [ ] Check inventory deduction
- [ ] Verify points awarded

### RSA System
- [ ] Create RSA request (as customer)
- [ ] Assign technician (as admin)
- [ ] Update status (as technician)
- [ ] Complete request
- [ ] View request history

### Rewards System
- [ ] Make purchase to earn points
- [ ] View available rewards
- [ ] Redeem reward
- [ ] Check points deduction
- [ ] View redemption history

## 📊 Sample Data

The system comes with:
- ✅ 5 sample products
- ✅ 5 sample rewards
- ✅ 1 admin user
- ✅ 1 technician user

## 🐛 Troubleshooting

### Can't login?
- Check database is imported
- Verify credentials
- Clear browser cache

### Database connection error?
- Check MySQL is running
- Verify database credentials in `config/database.php`
- Ensure database exists

### Page not found?
- Verify project folder name: `jk-motorparts`
- Check Apache is running
- Verify folder is in `htdocs` directory

## 📚 Next Steps

1. **Customize**: Update company name, logo, colors
2. **Configure**: Set up email notifications
3. **Secure**: Change default passwords
4. **Deploy**: Move to production server
5. **Backup**: Set up automatic backups

## 🎓 Learning Resources

- **Documentation**: See README.md
- **Installation**: See INSTALLATION.md
- **Code Structure**: Review project files
- **Database Schema**: Check database/schema.sql

## 💡 Tips

- Always backup database before updates
- Test in development before production
- Monitor activity logs regularly
- Keep passwords secure
- Update system regularly

---

**Ready to go!** Start exploring the system and customize it for your needs.

For detailed documentation, see README.md and INSTALLATION.md

