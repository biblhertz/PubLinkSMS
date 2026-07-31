ARG version=3.11
FROM python:${version}-slim


WORKDIR /app
ADD ./validator /app

# Copy requirements.txt FIRST
COPY requirements.txt .

RUN pip install --trusted-host pypi.python.org -r requirements.txt

RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

EXPOSE 8080
CMD ["/usr/local/bin/python", "/app/iiif-presentation-validator.py", "--hostname", "0.0.0.0"]
