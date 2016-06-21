BIN_NAME=docker-project
build: vendor
	php build-phar.php --bin="$(BIN_NAME)"
	chmod a+x $(BIN_NAME)

vendor:
	composer install --no-dev

clean:
	-rm $(BIN_NAME)

clean-all: clean
	-rm -rf vendor

install:
	cp $(BIN_NAME) /usr/local/bin/