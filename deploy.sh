#!/bin/bash

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Función para imprimir mensajes
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Función para verificar si el comando anterior falló
check_error() {
    if [ $? -ne 0 ]; then
        log_error "$1"
        exit 1
    fi
}

# Inicio del script
log_info "Iniciando deployment de Buro Laboral Backend..."

# 1. Detener contenedores actuales
log_info "Deteniendo contenedores existentes..."
docker-compose down
check_error "Error al detener contenedores"

# 2. Descartar cambios locales
# log_warning "Descartando cambios locales..."
# git restore .

# 3. Obtener últimos cambios
log_info "Obteniendo últimos cambios de la rama main..."
git pull origin main --no-edit
check_error "Error al hacer pull del repositorio"

# 4. Verificar si hay cambios
CHANGES=$(git log HEAD@{1}..HEAD --oneline)
if [ -z "$CHANGES" ]; then
    log_info "No hay cambios nuevos"
else
    log_info "Cambios detectados:"
    echo "$CHANGES"
fi

# 5. Configurar variables de entorno
export COMPOSE_BAKE=true
export CONTAINER_NAME="buro-laboral-backend"

# 6. Limpiar recursos huérfanos
log_info "Limpiando recursos huérfanos..."
docker-compose down --remove-orphans

# 7. Construir y levantar contenedores
log_info "Construyendo y levantando contenedores..."
docker-compose -f docker-compose.yml up -d --build
check_error "Error al construir y levantar contenedores"

# 8. Esperar a que el contenedor esté listo
log_info "Esperando a que el contenedor esté listo..."
sleep 5

# 9. Verificar que el contenedor está corriendo
if docker ps | grep -q "$CONTAINER_NAME"; then
    log_info "✓ Contenedor $CONTAINER_NAME está corriendo"
    
    # Mostrar logs recientes
    log_info "Logs recientes:"
    docker-compose logs --tail=20 buro-laboral-backend
else
    log_error "✗ El contenedor $CONTAINER_NAME no está corriendo"
    log_error "Mostrando logs:"
    docker-compose logs buro-laboral-backend
    exit 1
fi

# 10. Mostrar estado final
log_info "Estado de los contenedores:"
docker-compose ps

log_info "✓ Deployment completado exitosamente"
log_info "La aplicación está corriendo en http://localhost:8000"