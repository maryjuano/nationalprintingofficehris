deploy-staging:
	ssh ubuntu@staging2.acctechnology.ph '\
	cd hris-payroll-backend; pwd;\
	git pull; \
	composer install; \
	php artisan migrate; \
	php artisan optimize; \
	'

refresh-staging:
	ssh ubuntu@staging2.acctechnology.ph '\
	cd hris-payroll-backend; pwd;\
	git pull; \
	php artisan migrate:refresh; \
	'

migrate-staging:
	ssh ubuntu@staging2.acctechnology.ph '\
	cd hris-payroll-backend; pwd;\
	git pull; \
	php artisan migrate; \
	'

download-db-staging2:
	ssh ubuntu@staging2.acctechnology.ph "\
	mysqldump --user=ubuntu --password=ubuntu hris > /home/ubuntu/db_backups/hris-payroll-backend/$$(date +"staging_%m%d%y").sql; \
	"
	scp ubuntu@staging2.acctechnology.ph:/home/ubuntu/db_backups/hris-payroll-backend/$$(date +"staging_%m%d%y").sql ./db_backups/ \