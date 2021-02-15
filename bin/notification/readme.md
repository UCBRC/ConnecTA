1. Install Python 3 and Redis.
2. Install Python requirements by running `pip install -r requirements.txt`
3. You can now start the server! For Windows, run: `celery -A <module> worker -l info -P eventlet`. Otherwise, run: `celery -A server worker -l info`