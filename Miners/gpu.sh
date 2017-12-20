#!/bin/bash
cd $(dirname $0)
gnome-terminal --hide-menubar --geometry=80x20+400+300 -x bash -c 'cd xmrig-nvidia-gpu-cuda9/ && ./xmrig-nvidia || cd ../xmrig-nvidia-gpu-cuda8 ; ./xmrig-nvidia'
