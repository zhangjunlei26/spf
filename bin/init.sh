#!/usr/bin/env bash
BIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SPF_DIR="$(dirname $BIN_DIR)"
rm -f $BIN_DIR/spf
chmod +x $SPF_DIR/vendor/spf/spf.php
ln -s ../vendor/spf/spf.php $BIN_DIR/spf

