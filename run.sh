#!/bin/bash
# returns a correct exit code
! php detect_events.php | grep "problem";
