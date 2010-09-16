####################################################
Concurrent Editing Module
####################################################

# Maintainer Contact
Andreas Piening (Nickname: apiening)
<andreas (at) silverstripe (dot) com>

# Requirements
SilverStripe 2.4.0 or newer

# Installation

The module is automatically enabled after running dev/build

# Usage

The module stores all users currently editing a page in the database when a page is opened.
Other users which opened the same page in the CMS will be notified about the concurrent usage.
Storing and retrieving this state is performed by periodical ajax "pings".