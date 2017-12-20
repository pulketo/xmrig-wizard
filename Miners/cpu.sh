#!/bin/bash
cd $(dirname $0)
gnome-terminal --hide-menubar --geometry=80x20+100+100 -x bash -c 'cd xmrig-cpu-orig && ./xmrig'
