#!/bin/bash

if [[ ! -f "core/.git/config" ]]; then
    rm -rf ./core \
        ; git clone git@github.com:TYPO3/typo3.git ./core \
        && echo '>> Cloned TYPO3 code dev-main to "./core"' || echo '>> ERROR: Failed to clone TYPO3 core dev-main to "./core"' && exit 1
else
    echo '>> Git repository found in "./core" - please update checkout manually'
fi
