# Configuración de firewall para VPS (Ubuntu/Debian)
sudo ufw allow 8080/tcp    # WebSocket Reverb
sudo ufw allow 8000/tcp    # Laravel (si usas artisan serve)
sudo ufw allow 5173/tcp    # Vite (si lo necesitas)
sudo ufw reload

# Si usas iptables directamente:
sudo iptables -A INPUT -p tcp --dport 8080 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 8000 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 5173 -j ACCEPT
