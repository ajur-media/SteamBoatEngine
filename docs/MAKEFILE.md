```
PROJECT  = ""
VAR_ROOT = $(DESTDIR)/var/www/$(PROJECT)
WWW_ROOT = $(VAR_ROOT)/www

install:
	install -d $(VAR_ROOT)
	cp -r www $(VAR_ROOT)
	cp -r admin.cron $(VAR_ROOT)
	cp -r admin.tools $(VAR_ROOT)
	git rev-parse --short HEAD > $(WWW_ROOT)/_version
	git log --oneline --format=%B -n 1 HEAD | head -n 1 >> $(WWW_ROOT)/_version
	git log --oneline --format="%at" -n 1 HEAD | xargs -I{} date -d @{} +%Y-%m-%d >> $(WWW_ROOT)/_version
	cd $(WWW_ROOT)/ && composer install
	install -d $(VAR_ROOT)/logs
	install -d $(VAR_ROOT)/rss
	install -d $(WWW_ROOT)/sitemaps
	install -d $(WWW_ROOT)/cache

update:
	git pull

build:
	dpkg-buildpackage -rfakeroot

```

