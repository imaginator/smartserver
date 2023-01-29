Ubuntu 22.10 install on HP Gen8 

append

console=tty0 console=ttyS0,115200n8
0
so that grub looks like 

```                                                                                
                             GNU GRUB  version 2.06                             
                                                                                
 Ŀ                                                                              
 setparams 'Try or Install Ubuntu Server'                                       
                                                                                
         set gfxpayload=keep                                                    
         linux        /casper/vmlinuz console=tty0 console=ttyS0,115200 ---     
         initrd        /casper/initrd                                           
                                                                                
                                                                                
                                                                                
                                                                                
                                                                                
                                                                                
                                                                                
                                                                                
                                                                                
                                                                                
      Minimum Emacs-like screen editing is supported. TAB lists                 
      completions. Press Ctrl-x or F10 to boot, Ctrl-c or F2 for                
      a command-line or ESC to discard edits and return to the GRUB menu.       
```

Wait a long time for `Booting a command list`

### Sudo without a passowrd

sudo usermod -a -G sudo simon
%sudo   ALL=(ALL:ALL) ALL
%sudo   ALL=(ALL:ALL) NOPASSWD: ALL








