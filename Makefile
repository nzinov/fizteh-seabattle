usage :
	@echo "Here go possible comands for fizteh-seabattle developing"
	@echo "	deploy[_server/client] - deploys version in working tree to the producton server"
	@echo "	build - builds Release configuration of server"
	@echo "	build_debug - builds Debug configuration and restarts server"
	@echo "	test - make and start local server"
	@echo "	check - checks php syntax"
.PHONY : usage deploy test start stop
debugdir = server/bin/Debug
stop:
	@pkill mono || echo "Application is stopped"
start: build_debug
	pgrep mono || (mono $(debugdir)/fizteh-seabattle-gameserver.exe &)
test : check
	sudo cp -r client/* /var/www/fizteh
deploy : deploy_client deploy_server
deploy_client : check
	cp -r client/* client_deployment/repo/php/
	cd client_deployment && tar -czf production.tar.gz *
	rhc deploy client_deployment/production.tar.gz --app fizteh --hot-deploy
	rm client_deployment/production.tar.gz
	touch deploy_client
deploy_server : build
	cp server/bin/Release/* server_deployment/repo/diy
	cd server_deployment && tar -czf production.tar.gz *
	rhc deploy server_deployment/production.tar.gz --app server
	rm server_deployment/production.tar.gz
	touch deploy_server
build : server/*.cs
	xbuild server/fizteh-seabattle-gameserver.sln
	touch build
build_debug : server/*.cs stop
	xbuild /p:Configuration=Debug server/fizteh-seabattle-gameserver.sln
	(mono $(debugdir)/fizteh-seabattle-gameserver.exe &)
	touch build_debug
check : client/*.php
	cd client; find . -name \*.php -exec php -l "{}" \;

