<template>
  <div>
    <!-- 篩選 -->
    <el-card shadow="never" style="margin-bottom:16px">
      <el-row :gutter="12" align="middle">
        <el-col :span="4">
          <el-select v-model="year" style="width:100%" @change="fetchAll">
            <el-option v-for="y in yearOptions" :key="y" :label="`${y} 年`" :value="y" />
          </el-select>
        </el-col>
        <el-col :span="4">
          <el-select v-model="month" placeholder="全年" clearable style="width:100%" @change="fetchAll">
            <el-option v-for="m in 12" :key="m" :label="`${m} 月`" :value="m" />
          </el-select>
        </el-col>
      </el-row>
    </el-card>

    <!-- 結餘統計卡片 -->
    <el-row :gutter="16" style="margin-bottom:16px">
      <el-col :span="8">
        <el-card shadow="never" class="stat-card">
          <div class="stat-value income">NT$ {{ balance?.total_income?.toLocaleString() ?? '-' }}</div>
          <div class="stat-label">總收入</div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="never" class="stat-card">
          <div class="stat-value expense">NT$ {{ balance?.total_expense?.toLocaleString() ?? '-' }}</div>
          <div class="stat-label">總支出</div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="never" class="stat-card">
          <div class="stat-value" :class="balance?.total_balance >= 0 ? 'income' : 'expense'">
            NT$ {{ balance?.total_balance?.toLocaleString() ?? '-' }}
          </div>
          <div class="stat-label">結餘</div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16" style="margin-bottom:16px">
      <!-- 收支趨勢圖（全年才顯示） -->
      <el-col :span="14" v-if="!month">
        <el-card shadow="never" header="月份收支趨勢">
          <v-chart :option="barOption" style="height:280px" autoresize />
        </el-card>
      </el-col>
      <!-- 支出分類圓餅圖 -->
      <el-col :span="month ? 12 : 10">
        <el-card shadow="never" header="支出分類佔比">
          <v-chart :option="pieOption" style="height:280px" autoresize />
        </el-card>
      </el-col>
      <!-- 支出分類明細表 -->
      <el-col :span="month ? 12 : 24" style="margin-top: 0">
        <el-card shadow="never" header="支出分類明細" style="height:100%">
          <el-table :data="expenseData?.by_category ?? []" size="small">
            <el-table-column prop="category" label="分類" />
            <el-table-column label="金額">
              <template #default="{ row }">NT$ {{ Number(row.total).toLocaleString() }}</template>
            </el-table-column>
            <el-table-column prop="count" label="筆數" width="80" />
          </el-table>
        </el-card>
      </el-col>
    </el-row>

    <!-- 欠繳清單 -->
    <el-card shadow="never" header="欠繳清單">
      <el-table :data="overdueData?.bills ?? []" size="small" stripe>
        <el-table-column prop="unit.floor"  label="樓層" width="80" />
        <el-table-column prop="unit.number" label="室號" width="80" />
        <el-table-column label="住戶" width="120">
          <template #default="{ row }">{{ row.unit?.active_resident?.name ?? '-' }}</template>
        </el-table-column>
        <el-table-column label="年月" width="100">
          <template #default="{ row }">{{ row.year }}/{{ String(row.month).padStart(2,'0') }}</template>
        </el-table-column>
        <el-table-column label="金額" width="120">
          <template #default="{ row }">NT$ {{ Number(row.amount).toLocaleString() }}</template>
        </el-table-column>
        <el-table-column prop="due_date" label="截止日" width="120" />
        <el-table-column label="狀態" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 'overdue' ? 'danger' : 'warning'" size="small">
              {{ row.status === 'overdue' ? '逾期' : '未繳' }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
      <div class="overdue-footer" v-if="overdueData">
        共 {{ overdueData.count }} 筆，合計 NT$ {{ overdueData.total?.toLocaleString() }}
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { BarChart, PieChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import { reports } from '@/api'
import dayjs from 'dayjs'

use([BarChart, PieChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent, CanvasRenderer])

const year        = ref(dayjs().year())
const month       = ref(null)
const yearOptions = Array.from({ length: 5 }, (_, i) => dayjs().year() - i)

const balance     = ref(null)
const expenseData = ref(null)
const overdueData = ref(null)

onMounted(fetchAll)

async function fetchAll() {
  const params = { year: year.value, ...(month.value ? { month: month.value } : {}) }
  const [b, e, o] = await Promise.all([
    reports.balance({ year: year.value }),
    reports.expense(params),
    reports.overdue(params),
  ])
  balance.value     = b
  expenseData.value = e
  overdueData.value = o
}

const barOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { data: ['收入', '支出', '結餘'] },
  xAxis: { type: 'category', data: balance.value?.months.map((m) => `${m.month}月`) ?? [] },
  yAxis: { type: 'value' },
  series: [
    { name: '收入', type: 'bar', data: balance.value?.months.map((m) => m.income)  ?? [], itemStyle: { color: '#67c23a' } },
    { name: '支出', type: 'bar', data: balance.value?.months.map((m) => m.expense) ?? [], itemStyle: { color: '#e6a23c' } },
    { name: '結餘', type: 'bar', data: balance.value?.months.map((m) => m.balance) ?? [], itemStyle: { color: '#409eff' } },
  ],
}))

const pieOption = computed(() => ({
  tooltip: { trigger: 'item', formatter: '{b}: NT$ {c} ({d}%)' },
  legend: { orient: 'vertical', right: 10 },
  series: [{
    type: 'pie',
    radius: ['40%', '65%'],
    data: expenseData.value?.by_category.map((c) => ({ name: c.category, value: c.total, itemStyle: { color: c.color } })) ?? [],
  }],
}))
</script>

<style scoped>
.stat-card { text-align: center; padding: 8px 0; }
.stat-value { font-size: 24px; font-weight: bold; }
.stat-label { color: #909399; margin-top: 4px; }
.income  { color: #67c23a; }
.expense { color: #f56c6c; }
.overdue-footer { text-align: right; margin-top: 12px; color: #606266; font-size: 14px; }
</style>
