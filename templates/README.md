# 📄 Plantillas y Archivos de Ejemplo

Esta carpeta contiene plantillas y archivos de ejemplo para importación de datos.

## 📋 Archivos Disponibles

### Plantillas de Importación
- **plantilla_importacion_gaselag.csv** - Plantilla CSV para importar órdenes de servicio
- **data ejemplo programa.xlsm** - Archivo Excel de ejemplo con datos de prueba

## 📖 Cómo usar las plantillas

### Importación de Órdenes de Servicio

1. **Descargar plantilla**: Usa `plantilla_importacion_gaselag.csv` como referencia
2. **Formato requerido**: 33 columnas separadas por tabuladores
3. **No incluir encabezados**: Solo datos, sin fila de títulos
4. **Copiar desde Excel**: El formato se preserva automáticamente

### Estructura de Datos Requerida

El archivo debe contener las siguientes 33 columnas:

1. Item
2. Orden de servicio
3. Fecha OS
4. Cantidad de medidores
5. Tipo de Servicio
6. Programación Dia Retiro
7. Programación Hora Retiro
8. Programación dia VP
9. Programación Hora VP
10. CODIGO SEGURIDAD
11. Cliente
12. Centro de Servicio
13. Remesa
14. Usuario - Reclamante
15. Dirección
16. CUS
17. CUP
18. N° de Suministro
19. N° de Serie del Medidor
20. Marca del medidor
21. Modelo del medidor
22. Año de Fabricacion
23. Fabricante
24. Procedencia
25. Tipo Medidor
26. Diámetro Nominal (mm)
27. Q3 (m3/h)
28. Alcance
29. PMA (bar)
30. TMA (°C)
31. Clase de sensibilidad
32. Certificado de aprobación
33. N° de Certificado

## 💡 Consejos

- **Usar Excel de ejemplo**: `data ejemplo programa.xlsm` muestra el formato correcto
- **Mantener orden**: No alterar el orden de las columnas
- **Datos completos**: Llenar todos los campos requeridos
- **Formato de fecha**: Usar formato dd/mm/yyyy

## ⚠️ Notas Importantes

- Los datos se importan tal cual están en el archivo
- Validar datos antes de importar para evitar errores
- Solo usuarios con rol de Administrador pueden importar datos

## 🔙 Volver

- [README Principal](../README.md)
- [Guía de Importación](../docs/GUIA_IMPORTACION_ASIGNACIONES.md)

