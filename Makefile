SOURCES = lib/*.php

all: phpcs

phpcs:
	phpcs $(SOURCES)
    

