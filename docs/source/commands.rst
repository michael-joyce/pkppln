
Shell Commands
==============

The staging software includes shell commands for manipulating the
data. They are written in the Symfony framework, and are run though
the framework's console.

    $ ./app/console command [options] [arguments]

The console and commands are self-documenting.

   $ ./app/console help
   
   $ ./app/console command --help

Symfony includes a large number of commands for various purposes. Only
the commands relevant to the PLN are described here.

PLN Shell Commands
------------------

Shell commands are occasional-use utilities, meant to help correct problems.

.. toctree::
   :glob:
   :titlesonly:
      
   commands/shell/*
   

PLN Processing Commands
-----------------------

.. toctree::
   :glob:
   :titlesonly:
      
   commands/processing/*
   
