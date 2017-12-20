#!/bin/bash
cd $(dirname $0)
./cpu.sh &
./gpu.sh &
