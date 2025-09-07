#!/bin/bash
# setup.sh - Run this ONCE on your VDS

# Install Docker
curl -fsSL https://get.docker.com | sh

# Install Git
apt-get update && apt-get install -y git

# Clone your repository
cd /opt
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git last-frame-society
cd last-frame-society

# Build and run Docker container
docker build -t last-frame-app .
docker run -d --name last-frame-app -p 80:8080 --restart unless-stopped last-frame-app

echo "✅ Setup complete! Your site is running at http://$(curl -s ifconfig.me)"
