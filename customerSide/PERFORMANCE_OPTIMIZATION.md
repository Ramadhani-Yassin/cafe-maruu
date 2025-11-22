# Performance Optimization Guide

## Overview
This document outlines the performance optimizations implemented to resolve the lag and slow loading issues in the Café Maruu application.

## Issues Identified
1. **Multiple database queries on every page load** - Menu data was being queried from database on every page
2. **No caching mechanism** - Repeated database calls for the same data
3. **Inefficient database connections** - Connection overhead and lack of optimization
4. **Session data not cached** - User data queried repeatedly

## Solutions Implemented

### 1. Menu Data Caching
- **File-based caching** for menu items (Main Dishes, Drinks, Side Snacks)
- **Cache duration**: 1 hour (3600 seconds)
- **Cache location**: `customerSide/cache/menu_cache.json`
- **Benefits**: Eliminates repeated database queries for menu data

### 2. User Session Caching
- **Session-based caching** for user data (member name, points, VIP status)
- **Cache duration**: 5 minutes (300 seconds)
- **Benefits**: Reduces database queries for logged-in users

### 3. Database Connection Optimization
- **Connection pooling** with automatic reconnection
- **Query cache** enabled for MySQL 5.7+
- **Optimized timeout settings** (8 hours)
- **Automatic connection cleanup** on script termination

### 4. Cache Management
- **Cache clearing script**: `customerSide/cache/clear_cache.php`
- **Automatic cache invalidation** when data expires
- **Manual cache clearing** for admin updates

## Performance Improvements Expected

### Before Optimization
- **Page load time**: 2-5 seconds (depending on database load)
- **Database queries per page**: 3-4 queries
- **Connection overhead**: High (new connection per page)

### After Optimization
- **Page load time**: 0.1-0.5 seconds (cached data)
- **Database queries per page**: 0-1 queries (cached data)
- **Connection overhead**: Low (reused connections)

## Usage Instructions

### For Developers
1. **Clear cache when updating menu**: Run `clear_cache.php`
2. **Monitor performance**: Use `performance_monitor.php`
3. **Cache duration adjustment**: Modify `$cache_duration` in `header.php`

### For Users
- **First visit**: Slight delay as cache is created
- **Subsequent visits**: Fast loading with cached data
- **After updates**: Cache automatically refreshes

## Cache Files
- `customerSide/cache/menu_cache.json` - Menu data cache
- Session variables - User data cache

## Monitoring
- **Performance monitor**: `customerSide/performance_monitor.php`
- **Cache status**: Check cache file timestamps
- **Database queries**: Monitor query count and timing

## Troubleshooting

### Cache Not Working
1. Check if `cache/` directory exists and is writable
2. Verify file permissions (755 for directory, 644 for files)
3. Check disk space availability

### Performance Still Slow
1. Run performance monitor to identify bottlenecks
2. Check database server performance
3. Verify cache is being used (check file timestamps)

### Database Connection Issues
1. Check MySQL server status
2. Verify connection credentials in `config.php`
3. Check network connectivity

## Future Optimizations
1. **Redis caching** for better performance
2. **CDN integration** for static assets
3. **Database query optimization** with indexes
4. **Image optimization** and lazy loading
5. **Minification** of CSS/JS files

## Maintenance
- **Weekly**: Check cache performance
- **Monthly**: Review and adjust cache durations
- **After updates**: Clear relevant caches
- **Monitoring**: Use performance monitor regularly

## Support
For performance issues or optimization questions, refer to this document or contact the development team. 