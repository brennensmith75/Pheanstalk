Pheanstalk
==========

Pheanstalk is a pure PHP client for the [beanstalkd workqueue][1].

Pheanstalk is a work in progress by [Paul Annesley][2], but the existing code is stable and tested.

Not all commands are supported yet, but those that are supported are fully implemented:

  * bury
  * delete
  * ignore
  * kick
  * list-tubes-watched
  * list-tube-used
  * put
  * release
  * reserve
  * reserve-with-timeout
  * touch
  * use
  * watch

Commands yet to be implemented:

  * list-tubes
  * peek
  * peek-ready
  * peek-delayed
  * peek-buried
  * stats
  * stats-job
  * stats-tube

  [1]: http://xph.us/software/beanstalkd/
  [2]: http://paul.annesley.cc/
