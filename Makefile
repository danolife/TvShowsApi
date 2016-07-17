update-prod:
	git checkout master
	git pull --rebase
	bin/console c:c -e prod --no-warmup
	composer install
	bin/console d:s:u --force