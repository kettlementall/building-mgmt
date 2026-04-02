<template>
  <el-container class="layout">
    <!-- 側邊選單 -->
    <el-aside :width="collapsed ? '64px' : '220px'" class="aside">
      <div class="logo">
        <span v-if="!collapsed">大樓管理系統</span>
        <el-icon v-else><HomeFilled /></el-icon>
      </div>
      <el-menu
        :default-active="$route.path"
        router
        :collapse="collapsed"
        background-color="#1e2a38"
        text-color="#c0c4cc"
        active-text-color="#409eff"
      >
        <el-menu-item index="/dashboard">
          <el-icon><DataAnalysis /></el-icon>
          <template #title>總覽</template>
        </el-menu-item>
        <el-menu-item index="/units">
          <el-icon><OfficeBuilding /></el-icon>
          <template #title>戶別管理</template>
        </el-menu-item>
        <el-menu-item index="/residents">
          <el-icon><User /></el-icon>
          <template #title>住戶管理</template>
        </el-menu-item>
        <el-menu-item index="/fee-rules">
          <el-icon><Setting /></el-icon>
          <template #title>管理費規則</template>
        </el-menu-item>
        <el-divider style="border-color:#2d3d50; margin: 8px 0" />
        <el-menu-item index="/bills">
          <el-icon><Document /></el-icon>
          <template #title>帳單管理</template>
        </el-menu-item>
        <el-menu-item index="/expenses">
          <el-icon><Money /></el-icon>
          <template #title>支出管理</template>
        </el-menu-item>
        <el-divider style="border-color:#2d3d50; margin: 8px 0" />
        <el-menu-item index="/reports">
          <el-icon><TrendCharts /></el-icon>
          <template #title>財務報表</template>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <el-container direction="vertical">
      <!-- 頂部欄 -->
      <el-header class="header">
        <div class="header-left">
          <el-icon class="collapse-btn" @click="collapsed = !collapsed">
            <Fold v-if="!collapsed" /><Expand v-else />
          </el-icon>
          <span class="page-title">{{ $route.meta.title }}</span>
        </div>
        <div class="header-right">
          <span class="username">{{ authStore.user?.name }}</span>
          <el-button text @click="handleLogout">登出</el-button>
        </div>
      </el-header>

      <!-- 主內容 -->
      <el-main class="main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router    = useRouter()
const collapsed = ref(false)

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.layout { height: 100vh; }

.aside {
  background: #1e2a38;
  transition: width 0.2s;
  overflow: hidden;
}

.logo {
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 15px;
  font-weight: bold;
  background: #16212e;
  white-space: nowrap;
  overflow: hidden;
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  border-bottom: 1px solid #ebeef5;
  padding: 0 20px;
}

.header-left { display: flex; align-items: center; gap: 12px; }
.header-right { display: flex; align-items: center; gap: 8px; }
.collapse-btn { cursor: pointer; font-size: 20px; }
.page-title { font-size: 16px; font-weight: 600; }
.username { color: #606266; font-size: 14px; }

.main { background: #f5f7fa; padding: 20px; overflow-y: auto; }
</style>
